<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @since      1.0.0
 *
 * @package    checkout-plugins-woocommerce
 * @subpackage checkout-plugins-woocommerce/includes
 */

use Constants as LatitudeConstants;

/**
 * The core plugin class
 *
 * This is the Latitude Checkout - WooCommerce Payment Gateway Class.
 */
if (!class_exists('WC_LatitudeCheckoutGateway')) {
    class WC_LatitudeCheckoutGateway extends WC_Payment_Gateway
    {
        /**
         * Protected static variable
         *
         *
         * @var     WC_LatitudeCheckoutGateway|null     $instance           Latitude Checkout Payment Gateway Object Instance. Defaults to null.
         *
         */

        protected static $instance = null;

        /**
         * Protected static variable
         *
         *
         * @var     WC_Logger|null                      $log                WC_logger object instance. Defaults to null.
         * @var		bool|null			                $log_enabled	    Whether or not logging is enabled. Defaults to null.
         *
         */

        protected static $log = null,
            $log_enabled = null;

 

        /**
         * Protected variables.
         *
         *
         * @var     string     $merchant_id         Merchant Unique ID configuration. Set at the admin page.
         * @var     string     $merchant_secret     Merchant Secret Key configuration. Set at the admin page.
         * @var     string     $test_mode           Whether payment gateway will be run in test mode or not. Set at the admin page.
         * @var     string     $widget_data         Product widget configuration. Set at the admin page.
         *
         */
        protected $merchant_id, $merchant_secret, $test_mode;

        /**
         * Private variables.
         *
         * @var		string	$include_path			Path to where this class's includes are located. Populated in the class constructor.
         */
        private $include_path;

        /**
         * Class constructor. Called when an object of this class is instantiated.
         *
         *
         */
        public function __construct()
        {
            $this->include_path = WC_LATITUDE_GATEWAY__PLUGIN_DIR . 'includes';

            $this->id = LatitudeConstants::WC_LATITUDE_GATEWAY_ID;
            $this->method_title = __(
                LatitudeConstants::WC_LATITUDE_GATEWAY_NAME,
                'woo_latitudecheckout'
            );
            $this->method_name = __(
                LatitudeConstants::WC_LATITUDE_GATEWAY_NAME,
                'woo_latitudecheckout'
            );
            $this->method_description = sprintf(
                __(
                    'Use %s as payment method for WooCommerce orders.',
                    'woo_latitudecheckout'
                ),
                LatitudeConstants::WC_LATITUDE_GATEWAY_NAME
            );

            $this->icon = apply_filters('woocommerce_gateway_icon', 10, 2);
            $this->has_fields = true; // needed to be true for customizing payment fields
            $this->title = __(
                LatitudeConstants::WC_LATITUDE_GATEWAY_NAME,
                'woo_latitudecheckout'
            );

            $this->init_form_fields();
            $this->init_settings();
            $this->refresh_configuration();
        }

        /**
         * Instantiate the class if no instance exists. Return the instance.
         *
         */
        public static function get_instance()
        {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Adds the Latitude Checkout Payments Gateway to WooCommerce
         *
         */

        public function add_latitudecheckoutgateway($gateways)
        {
            $gateways[] = 'WC_LatitudeCheckoutGateway';
            return $gateways;
        }

        /**
         *  Default values for the plugin's Admin Form Fields
         */

        public function init_form_fields()
        {
            include "{$this->include_path}/Admin_Form_Fields.php";
        }

        /**
		 * Adds/Updates admin settings - needed to overload explicitly to update admin settings in some shops
		 *
		 * Note:	Hooked onto the "woocommerce_update_options_payment_gateways_" Action.
		 * 
		 */
		public function process_admin_options() {
			parent::process_admin_options();
 
		}

        /**
         * Refresh cached configuration to ensure properties are up to date
         *
         * Note:	Hooked onto the "woocommerce_update_options_payment_gateways_{$gateway->id}" Action.
         *
         */
        public function refresh_configuration()
        { 
            if (array_key_exists('merchant_id', $this->settings)) {
                $this->merchant_id = $this->settings['merchant_id'];
            }
            if (array_key_exists('merchant_secret', $this->settings)) {
                $this->merchant_secret = $this->settings['merchant_secret'];
            }
            $this->test_mode = true;
            if (array_key_exists('testmode', $this->settings)) {
                $this->test_mode = 'yes' === $this->settings['testmode'];
            }
            self::$log_enabled = $this->test_mode;
            if (array_key_exists('widget_content', $this->settings)) {
                $this->merchant_secret = $this->settings['widget_data'];
            }
        }

        /**
         * Get plugin version constant
         */
        public function get_plugin_version() 
        {
            return WC_LATITUDE_GATEWAY__PLUGIN_VERSION;
        }
 
        /**
         * Get the Merchant ID from our user settings.
         */
        public function get_merchant_id()
        {
            return $this->settings['merchant_id'];
        }

        /**
         * Get the Merchant Secret Key from our user settings.
         */
        public function get_secret_key()
        {
            return $this->settings['merchant_secret'];
        }

        /**
         * Get the Test Mode Enabled from our user settings.
         */
        public function get_test_mode()
        {
            $this->test_mode = 'yes' === $this->settings['testmode'];
            return $this->test_mode;
        }

        /**
         * Get the Widget settings from our user settings.
         */
        public function get_widget_data()
        {
            echo '<div id="latitude-banner-container"></div>';
            $widgetData = $this->settings['widget_content'];
            $obj = json_decode($widgetData, true);
            $product = wc_get_product();
            $category = get_the_terms($product->id, 'product_cat');
            wp_enqueue_script(
                'latitude_widget_js',
                '/wp-content/plugins/checkout-plugins-woocommerce/js/woocommerce.js'
            );
            wp_localize_script(
                'latitude_widget_js',
                'latitude_widget_js_vars',
                [
                    'page' => 'product',
                    'container' => 'latitude-banner-container',
                    'widgetSettings' => $obj,
                    'merchantId' => $this->merchant_id,
                    'currency' => get_woocommerce_currency(),
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $category[0]->name,
                    'price' => $product->price,
                    'sku' => $product->sku,
                    'assetUrl' => $this->get_widget_asset_src(),
                ]
            );
        }
 

        /**
         *
         * Hooked onto the "woocommerce_gateway_icon" filter.
         *
         */
        function filter_latitude_gateway_icon($icon, $gateway_id)
        {
            if ($gateway_id != LatitudeConstants::WC_LATITUDE_GATEWAY_ID) {
                return $icon;
            }

            $icon_url = LatitudeConstants::AU_ICON_URL;
            $icon_alt_text = LatitudeConstants::AU_ICON_ALT_TEXT;
            if ('NZD' == get_woocommerce_currency()) {
                $icon_url = LatitudeConstants::NZ_ICON_URL;
                $icon_alt_text = LatitudeConstants::NZ_ICON_ALT_TEXT;
            }

            ob_start();
            ?><img src="<?php echo $icon_url; ?>" alt="<?php echo $icon_alt_text; ?>" class="checkout-logo__latitude" /><?php return ob_get_clean();
        }

        /**
         * Styles for Latitude interest free
         *
         */
        public function add_checkout_custom_style()
        {
            wp_enqueue_style( 
                'latitude_checkout-styles', 
                plugins_url( 'checkout-plugins-woocommerce/css/latitude.css')
            );
        }

        /**
         *
         * Hooked onto the "woocommerce_order_button_text" filter.
         *
         */
        public function filter_place_order_button_text($button)
        {
            $current_payment_method = WC()->session->get(
                'chosen_payment_method'
            ); // The chosen payment
            if (
                $current_payment_method ==
                LatitudeConstants::WC_LATITUDE_GATEWAY_ID
            ) {
                $button = 'Choose a plan';
            }
            return $button;
        }

        /**
         * Display as a payment option on the checkout page.
         *
         */

        public function payment_fields()
        {
            // TODO: additional field validations here when needed
            # Give other plugins a chance to manipulate or replace the HTML echoed by this funtion.
           
            ?>   
            <div id="latitude-payment--main"> 
            <div style="display: flex !important; justify-content: center !important">
            <svg
                version="1.1"
                style="height: 50px !important"
                id="L4"
                xmlns="http://www.w3.org/2000/svg"
                xmlns:xlink="http://www.w3.org/1999/xlink"
                x="0px"
                y="0px"
                viewBox="0 0 100 100"
                enable-background="new 0 0 0 0"
                xml:space="preserve"
            >
                <circle fill="#0046AA" stroke="none" cx="6" cy="50" r="6">
                <animate
                    attributeName="opacity"
                    dur="2s"
                    values="0;1;0"
                    repeatCount="indefinite"
                    begin="0.1"
                />
                </circle>
                <circle fill="#0046AA" stroke="none" cx="26" cy="50" r="6">
                <animate
                    attributeName="opacity"
                    dur="2s"
                    values="0;1;0"
                    repeatCount="indefinite"
                    begin="0.2"
                />
                </circle>
                <circle fill="#0046AA" stroke="none" cx="46" cy="50" r="6">
                <animate
                    attributeName="opacity"
                    dur="2s"
                    values="0;1;0"
                    repeatCount="indefinite"
                    begin="0.3"
                />
                </circle>
            </svg>
            </div>
            <p style="margin-top: 14px !important; margin-bottom: 14px !important">
                You will be redirected to Latitude complete your order
            </p>
            </div>  
            <div id="latitude-payment--footer"></div> 
            <?php
           
            wp_enqueue_script(
                'latitude_paymentfield_js',
                '/wp-content/plugins/checkout-plugins-woocommerce/js/woocommerce.js'
            );
            wp_localize_script(
                'latitude_paymentfield_js',
                'latitude_widget_js_vars',
                [
                    'page' => 'checkout',
                    'container' => [
                        'footer' => 'latitude-payment--footer',
                        'main' => 'latitude-payment--main',
                    ], 
                    'merchantId' => $this->merchant_id,
                    'currency' => get_woocommerce_currency(), 
                    'assetUrl' => $this->get_payment_fields_src(),
                    'widgetSettings' => '',
                ]
            );


        }

        /**
         * Returns the asset url source to display in the payment fields at the checkout page.
         *
         */

        protected function get_payment_fields_src()
        {
            $url = __(
                ($this->get_test_mode()
                    ? LatitudeConstants::PAYMENT_FIELDS_URL_TEST
                    : LatitudeConstants::PAYMENT_FIELDS_URL_PROD) .
                    '/assets/content.js?platform=' .
                    LatitudeConstants::WC_LATITUDE_GATEWAY_PLATFORM .
                    '&merchantId=' .
                    $this->get_merchant_id()
            );
            return $url;
        }

        /**
         * Returns the asset url to display widget at product page.
         *
         */
        protected function get_widget_asset_src()
        {
            $url = __(
                ($this->get_test_mode()
                    ? LatitudeConstants::WIDGETS_URL_TEST
                    : LatitudeConstants::WIDGETS_URL_PROD) .
                    '/assets/content.js?platform=' .
                    LatitudeConstants::WC_LATITUDE_GATEWAY_PLATFORM .
                    '&merchantId=' .
                    $this->get_merchant_id()
            );
            return $url;
        }

        public function validate_checkout_fields( $fields, $errors ){
 
            if ( preg_match( '/\\d/', $fields[ 'billing_first_name' ] ) || preg_match( '/\\d/', $fields[ 'billing_last_name' ] )  ){
                $errors->add( 'validation', 'Your first or last name contains a number.' );
                return;
            } 
            //TODO : Add additional field validations here
            
        }

        /**
         * Default process payment
         *
         */

        public function process_payment($order_id)
        {
            $this->log_info(
                __(
                    'Processing payment using ' .
                        LatitudeConstants::WC_LATITUDE_GATEWAY_NAME .
                        ' payment method.'
                )
            );
            if (!$order_id) {
                $this->log_error(
                    'Order ID cannot be null when processing payment.'
                );
                return;
            }

            $purchase_request = new Latitude_Purchase_Request();
            $payload = $purchase_request->build_parameters($order_id);
            $this->log_debug($payload);

            $checkout_service = new Latitude_Checkout_Service();
            $response = $checkout_service->send_purchase_request($payload);

            $order = wc_get_order($order_id);

    
            //$order->get_payment_method();
            $this->log_debug(__('Check ORDER PAYMENT METHOD: ' . $order->get_payment_method()));
            if ($response == false) {
                return $this->redirect_to_cart_on_api_error(
                    $order,
                    'Purchase Request was not valid. Please contact Merchant.'
                );
            }

            if (is_array($response) && $response['result'] == 'failure') { 
                $error_string = __( "Purchase Request returned with error : {$response['error']}.");
                $this->log_error($error_string);
                $order->add_order_note(__( $error_string , 'woo_latitudecheckout' ));
                $order->update_status( LatitudeConstants::WC_ORDER_FAILED, __( 'Purchase request API failed. ', 'woo_latitudecheckout' ) );
                return $this->redirect_to_cart_on_api_error($order, 'Purchase Request was not valid. Please contact Merchant.');
            }

            $rsp_body = $response['response'];
            $result = $rsp_body['result'];
            $this->log_debug($rsp_body);
            if ($result == 'pending') {
                $is_valid = $this->validate_order_response(
                    $rsp_body,
                    $order
                );
                if ($is_valid !== true) { 
                    $error_string = __(
                        " Invalid order response. Error: {$is_valid['error']} "  
                    );
                    $this->log_error($error_string);
                    return $this->redirect_to_cart_on_error(
                        $order,
                        $error_string
                    );
                }

                $redirect_url = $rsp_body['redirectUrl']; 
                if (empty($redirect_url)) {
                    return $this->redirect_to_cart_on_error(
                        $order,
                        'Latitude Interest Free Gateway is not reachable.'
                    );
                }
                $this->log_debug(__('++redirectUrl: ' . $redirect_url));
                //$order->update_status( LatitudeConstants::WC_ORDER_PENDING);
                // wp_safe_redirect($redirect_url);
                // exit;
                if (!is_ajax()) {
                    wp_safe_redirect($redirect_url);
                    exit();
                }
                wp_send_json([
                    'result' => 'success',
                    'redirect' => $redirect_url,
                ]);

            } elseif ($result == 'failed') {
                $error_string = $rsp_body['error'];
                if (empty($error_string)) {
                    $error_string = 'Purchase Request was not valid. Please contact Merchant.';
                }     
                $this->log_error( __( "Purchase Request returned with error : {$error_string}."));
                $order->add_order_note(__(  "Purchase Request returned with error : {$error_string}.", 'woo_latitudecheckout' ));
                $order->update_status( LatitudeConstants::WC_ORDER_FAILED, __( 'Purchase request failed. ', 'woo_latitudecheckout' ) );
                return $this->redirect_to_cart_on_api_error($order, $error_string);
            } else {
                // Purchase request returned unexpected result (neither pending nor failed)
                $error_string =  __(
                    'Unexpected result received from purchase request: ' .
                        $result
                );
                $this->log_error($error_string); 
                return $this->redirect_to_cart_on_error(
                    $order,
                    $error_string
                );
            }
        }


        /**
         *
         * Displays the error message on the cart.
         *
         */
        private function redirect_to_cart_on_api_error($order, $error_string)
        { 
            wc_add_notice(__($error_string, 'woo_latitudecheckout'), 'error');
            return [
                'result' => 'failure',
                'redirect' => $order->get_checkout_payment_url(false),
            ];
        }       

        /**
         *
         * Displays the error message on the cart.
         *
         */
        private function redirect_to_cart_on_error($order, $error_string)
        {
            $order->add_order_note(__( $error_string , 'woo_latitudecheckout' ));
            $order->update_status( LatitudeConstants::WC_ORDER_FAILED, __( 'Purchase request not valid. ', 'woo_latitudecheckout' ) );
            wc_add_notice(__( "Payment failed. Please try again.", 'woo_latitudecheckout'), 'error');
            return [
                'result' => 'failure',
                'redirect' => $order->get_checkout_payment_url(false),
            ];
        }       
 
        /**
         *
         * Validates contents of the purchase_request to match current order.
         *
         */
        protected function validate_order_response($response, $order)
        { 
            if ($response['merchantId'] != $this->merchant_id) {
                return [ 
                    'valid' => false,
                    'error' => 'Failed to confirm Merchant ID.'
                ]; 
            }
            if ($response['merchantReference'] != $order->get_id()) { 
                return [ 
                    'valid' => false,
                    'error' => 'Failed to confirm Order reference.'
                ]; 
            }
            if ($response['amount'] != $order->get_total()) { 
                return [ 
                    'valid' => false,
                    'error' => 'Failed to confirm transaction amount.'
                ]; 
            }
            if ($response['currency'] != $order->get_currency()) { 
                return [ 
                    'valid' => false,
                    'error' => 'Failed to confirm transaction urrency'
                ]; 
            }
            return true;
        }

         /**
         *
         * Hooked onto the "woocommerce_before_cart" action.
         *
         */

        public function on_load_cart_page()
        { 
            $current_payment_method = WC()->session->get( 'chosen_payment_method'  );   
            if ( $current_payment_method !=  LatitudeConstants::WC_LATITUDE_GATEWAY_ID ) {
               return;
            }   

            if (array_key_exists('cancel_order', $_GET) && array_key_exists('order_id', $_GET) && 
                    $_GET['cancel_order'] === 'true') {
                $order_id = (int)$_GET['order_id']; 
                $this->log_info(__("Order cancelled by customer:{$order_id}"));
                $order = wc_get_order($order_id); 
                $order->update_status( LatitudeConstants::WC_ORDER_CANCELLED); 
            }

        }

        /**
         *
         * Hooked onto the "woocommerce_endpoint_order-pay_title" filter.
         *
         */
        public function filter_order_pay_title($old_title, $payment_declined)
        {
            //order-pay
            if ($payment_declined) {
                return 'Payment failed';
            }
            return old_title;
        }

        /**
         *
         * Hooked onto the "woocommerce_receipt_{$gateway->id}" action.
         *
         */
        public function receipt_page($order_id)
        {
            $order = wc_get_order($order_id);
            $is_pending = $this->is_order_pending($order);
            $this->log_debug(
                __('(on receipt_page) is_pending: ' . $is_pending)
            );
            if ($is_pending) {
                wc_print_notice('Payment declined for this order. ');
                apply_filters(
                    'woocommerce_endpoint_order-pay_title',
                    'Pay for order',
                    true
                );
            }
        }
 
         /**
         *
         * Hooked onto the "woocommerce_api_latitude_checkout" action.
         *
         */

        public function on_latitude_checkout_callback()
        {
            $this->log_debug('on_latitude_checkout_callback');

            if (array_key_exists('gatewayReference', $_GET)) {
                $gatewayReference = $_GET['gatewayReference'];
            }
            if (array_key_exists('transactionReference', $_GET)) {
                $transactionReference = $_GET['transactionReference'];
            }
            if (array_key_exists('merchantReference', $_GET)) {
                $order_id = $merchantReference = $_GET['merchantReference'];
            }
            $this->log_debug(
                "merchantReference: {$merchantReference}, transactionReference: {$transactionReference}, gatewayReference: {$gatewayReference}"
            );
            if (
                empty($gatewayReference) ||
                empty($transactionReference) ||
                empty($merchantReference)
            ) {
                 
                wc_print_notice('Failed to verify order details. Please contact merchant.');
                wp_redirect(wc_get_cart_url());
                exit();
            }

            $payload = [
                'gatewayReference' => $gatewayReference,
                'transactionReference' => $transactionReference,
                'merchantReference' => $merchantReference,
            ];

            $order = wc_get_order($order_id);
            // // check order status
            // $is_order_pending = $this->is_order_pending($order);
            // if (!$is_order_pending) {
            //     $this->log_error(
            //         'Cannot verify purchase when order is no longer pending.'
            //     );
            //     $order->add_order_note(
            //         sprintf(
            //             __(
            //                 'Order status is not pending for payment, cannot proceed to verify. Transaction reference: %s.',
            //                 'woo_latitudecheckout'
            //             ),
            //             $transactionReference
            //         )
            //     );
            //     $order->update_status( LatitudeConstants::WC_ORDER_FAILED, __( 'Purchase cannot verify order on this status.', 'woo_latitudecheckout' ) ); 
            //     $this->redirect_on_verify_api_failure($order); 
            // }

            $checkout_service = new Latitude_Checkout_Service();
            $response = $checkout_service->verify_purchase_request($payload);

            if ($response === false) {
                $this->log_error('Verify Purchase API failed.');
                $order->add_order_note(
                    sprintf(
                        __(
                            '%s failed at Verify Purchase API.',
                            'woo_latitudecheckout'
                        ),
                        $order->get_payment_method_title()
                    )
                );
                $order->update_status( LatitudeConstants::WC_ORDER_FAILED, __( 'Verify Purchase API failed.', 'woo_latitudecheckout' ) ); 
                $this->redirect_on_verify_api_failure($order);
            } elseif (is_array($response)) {
                $rsp_body = $response['response'];
                $this->log_debug('verify_purchase_request() returned data');
                $this->log_debug($rsp_body);
                $result = $rsp_body['result'];
                $transactionType = $rsp_body['transactionType'];
                $promotionReference = $rsp_body['promotionReference'];
                $message = $rsp_body['message'];

                if ($result == 'completed') {
                    if ($transactionType == 'sale') {
                        $this->log_info(
                            "WooCommerce Order #{$order_id} transaction is \"completed\"."
                        );
                        $order->add_order_note(
                            sprintf(
                                __(
                                    'Payment approved. Transaction reference: %s, Gateway reference: %s.',
                                    'woo_latitudecheckout'
                                ),
                                $transactionReference,
                                $gatewayReference
                            )
                        );
                    } elseif ($transactionType == 'authorisation') {
                        $this->log_info(
                            "WooCommerce Order #{$order_id} transaction is on \"authorisation\"."
                        );
                        $order->add_order_note(
                            sprintf(
                                __(
                                    'Payment under authorisation. Transaction reference: %s, Gateway reference: %s',
                                    'woo_latitudecheckout'
                                ),
                                $transactionReference,
                                $gatewayReference
                            )
                        );
                    } else {
                        $this->log_info(
                            "WooCommerce Order #{$order_id} transaction is \"accepted as {$transactionType}\"."
                        );
                        $order->add_order_note(
                            sprintf(
                                __(
                                    'Payment under $s. Transaction reference: %s, Gateway reference: %s',
                                    'woo_latitudecheckout'
                                ),
                                $transactionType,
                                $transactionReference,
                                $gatewayReference
                            )
                        );
                    }
                    $order->payment_complete();
                    $order->update_meta_data(
                        'gatewayReference',
                        $gatewayReference
                    );
                    $order->update_meta_data(
                        'transactionReference',
                        $transactionReference
                    );
                    $order->update_meta_data(
                        'promotionReference',
                        $promotionReference
                    );
                    $order->update_meta_data(
                        'transactionType',
                        $transactionType
                    );
                    $order->save();
                    wc_empty_cart();
                    wp_redirect($order->get_checkout_order_received_url());
                    exit();
                } else {
                    if (!empty($message)) {
                        $this->log_warning(
                            "Verfiy Purchase Error Message:{$message}."
                        );
                    }
                    $this->log_warning(
                        "Payment declined for WooCommerce Order #{$order_id}."
                    ); 
                    $order->update_status( LatitudeConstants::WC_ORDER_FAILED, __(  sprintf(
                        __(
                            'Payment declined. Transaction reference: %s, Gateway reference: %s.',
                            'woo_latitudecheckout'
                        ),
                        $transactionReference, $gatewayReference
                    ), 'woo_latitudecheckout' ) ); 
                    $this->redirect_on_verify_api_failure($order);
                }
            } else {
                // TODO
                $this->log_error(
                    'Verify Purchase API returned invalid response.'
                );
                $order->update_status( LatitudeConstants::WC_ORDER_FAILED, __( 'Verify Purchase API failed.', 'woo_latitudecheckout' ) ); 
                redirect_on_verify_api_failure($order);
            }
            return;
        }
         
 
        private function  redirect_on_verify_api_failure( $order)
        {  
            wc_add_notice(__('Payment declined for this order. Please contact merchant.', 'woo_latitudecheckout'), 'error');
            wp_redirect(wc_get_checkout_url()); 
            exit; 
        }

        /**
         *
         * Checks the pending status of the order
         *
         */

        private function is_order_pending($order)
        {
            $is_pending = false;
            if (method_exists($order, 'has_status')) {
                $is_pending = $order->has_status('pending');
            } else {
                $this->log_debug("order status: {$order->status}");
                if ($order->status == 'pending') {
                    $is_pending = true;
                }
            }
            return $is_pending;
        }
 
        /**
         *
         * Display additional order details in admin
         *
         */

        public function display_order_data_in_admin($order)
        {
            if ($order->get_payment_method() != $this->id) {
                return;
            } 
            
            $gatewayRef =  $order->get_meta('gatewayReference');
            $transactionRef = $order->get_meta('transactionReference');
            $promotionRef = $order->get_meta('promotionReference');
            $transType = $order->get_meta('transactionType');
            if ( empty($gatewayRef) && empty($transactionRef) && empty($promotionRef) && empty($transType)) {
                return;
            }
            
            ?> 
             <p class="form-field form-field-wide"> <br>
                <div class="latitude_payment_details">
                <h3><?php esc_html_e(
                    'Latitude Interest Free Payment Details',
                    'woo_latitudecheckout'
                ); ?></h3>
                    <?php 
                    echo '<p><strong>' .
                        __('Gateway Reference') .
                        ': </strong><br>' .
                        $gatewayRef .
                        '<br></p>';
                    echo '<p><strong>' .
                        __('Transaction Reference') .
                        ': </strong><br>' .
                        $transactionRef .
                        '<br></p>';
                    echo '<p><strong>' .
                        __('Promotion Reference') .
                        ': </strong><br>' .
                        $promotionRef .
                        '<br></p>';
                    echo '<p><strong>' .
                        __('Transaction Type') .
                        ': </strong><br>' .
                        $order->get_meta('transactionType') .
                        '<br></p>';?>
                </div></p>
            <?php
        }

        /**
         * Logging method for debugging.
         */
        public static function log_debug($message)
        {
            if (is_null(self::$log_enabled)) {
                # Get the settings key for the plugin
                $gateway = new WC_LatitudeCheckoutGateway();
                $settings_key = $gateway->get_option_key();
                $settings = get_option($settings_key);

                if (array_key_exists('test_mode', $settings)) {
                    self::$log_enabled = $settings['test_mode'] == 'yes';
                } else {
                    self::$log_enabled = false;
                }
            }
            if (self::$log_enabled) {
                if (is_null(self::$log)) {
                    self::$log = wc_get_logger();
                }
                $message = self::format_message($message);
                self::$log->debug($message, ['source' => 'latitude_checkout']);
            }
        }

        /**
         * Logging method for warnings
         */
        public static function log_warning($message)
        {
            if (is_null(self::$log)) {
                self::$log = wc_get_logger();
            }
            $message = self::format_message($message);
            self::$log->warning($message, ['source' => 'latitude_checkout']);
        }

        /**
         * Logging method for info
         */
        public static function log_info($message)
        {
            if (is_null(self::$log)) {
                self::$log = wc_get_logger();
            }
            $message = self::format_message($message);
            self::$log->info($message, ['source' => 'latitude_checkout']);
        }

        /**
         * Logging method for error
         */
        public static function log_error($message)
        {
            if (is_null(self::$log)) {
                self::$log = wc_get_logger();
            }
            $message = self::format_message($message);
            self::$log->error($message, ['source' => 'latitude_checkout']);
        }

        /**
         * Format message for logging
         */
        private static function format_message($message)
        {
            if (is_array($message)) {
                $message = print_r($message, true);
            } elseif (is_object($message)) {
                $ob_get_length = ob_get_length();
                if (!$ob_get_length) {
                    if ($ob_get_length === false) {
                        ob_start();
                    }
                    var_dump($message);
                    $message = ob_get_contents();
                    if ($ob_get_length === false) {
                        ob_end_clean();
                    } else {
                        ob_clean();
                    }
                } else {
                    $message = '(' . get_class($message) . ' Object)';
                }
            }
            return $message;
        }
    }
}