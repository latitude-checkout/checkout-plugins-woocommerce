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
            $this->method_description = sprintf(
                __(
                    'Use %s as payment method for WooCommerce orders.',
                    'woo_latitudecheckout'
                ),
                LatitudeConstants::WC_LATITUDE_GATEWAY_NAME
            );

            $this->icon = apply_filters('woocommerce_gateway_icon', 10, 2);
            $this->has_fields = true; // needed to be true for customizing payment fields
            $this->title = _(
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
         * Overwriting theme CSS to ensure #order_review stays full width to accommodate Latitude Checkout logo.
         *
         */
        public function add_checkout_custom_style()
        {
            wp_enqueue_style(
                'checkout',
                plugins_url('checkout-plugins-woocommerce/css/checkout.css')
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
            $icon_alt_text = 'Latitude Interest Fee';
            if ('NZD' == get_woocommerce_currency()) {
                $icon_url = LatitudeConstants::NZ_ICON_URL;
                $icon_alt_text = 'GEM Interest Fee';
            }
            ob_start();
            ?><img src="<?php echo $icon_url; ?>" alt="<?php echo $icon_alt_text; ?>" /><?php return ob_get_clean();
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
                You will be redirected to Latitude checkout to complete your order
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
         * Override order creation
         *
         */

        public function create_order_quote($null, $checkout)
        {
            $this->log_debug('create_order_quote');

            $post_data = $checkout->get_posted_data();
            $this->log_debug(json_encode($post_data));

            # Set session value of "lc_account_exists" to check for customer signed up at checkout or not
            WC()->session->set(
                'latitude_account_exists',
                isset($post_data['createaccount']) ? $post_data['createaccount'] : 0
            );

            if (
                $post_data['payment_method'] !=
                LatitudeConstants::WC_LATITUDE_GATEWAY_ID
            ) { 
                $this->log_error("Cannot create order for payments other than " . LatitudeConstants::WC_LATITUDE_GATEWAY_NAME);
                return -1;
            }
  
              
            $post_array = array( 
                'post_title' => 'Latitude Checkout Order', 
                'post_content' => 'Redirecting to Latitude Interest Free to complete payment', 
                'post_status' => 'publish',  
                'post_type' => array('latitudecheckout_order'),  //BUG-FOUND!! post_type not reflected
             ); 

            
            // create a quote
            $post_id = wp_insert_post(  $post_array, true );   
            if (is_wp_error($post_id)){
                $errors_str = implode($post_id->get_error_messages(), ' ');
				$this->log_error("Could not create \"latitudecheckout_order\" post. WordPress threw error(s): {$errors_str})");
                $error = new WP_Error('CreateOrderError', $errors_str, 'woo_latitudecheckout'); 
				return $error;
            } else {
                $cart = WC()->cart;
                $cart_hash = $cart->get_cart_hash();
				
                $currency = get_woocommerce_currency();  
				$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
				$shipping_packages = WC()->shipping()->get_packages();

				$customer_id = apply_filters( 'woocommerce_checkout_customer_id', get_current_user_id() );
				$order_vat_exempt = ( $cart->get_customer()->get_is_vat_exempt() ? 'yes' : 'no' );
				
				$prices_include_tax = ( get_option( 'woocommerce_prices_include_tax' ) === 'yes' );
				$customer_ip_address = WC_Geolocation::get_ip_address();
				$customer_user_agent = wc_get_user_agent();
				$customer_note = ( isset( $post_data['order_comments'] ) ? $post_data['order_comments'] : '' );
				$payment_method =  $post_data['payment_method'];
				$shipping_total = $cart->get_shipping_total();
				$discount_total = $cart->get_discount_total();
				$discount_tax = $cart->get_discount_tax();
				$cart_tax = $cart->get_cart_contents_tax() + $cart->get_fee_tax();
				$shipping_tax = $cart->get_shipping_tax();
				$total = $cart->get_total( 'edit' );

                $this->log_info('Creating payload for Purchase Request..'); 
                $purchase_request = new Latitude_Purchase_Request();
                $payload = $purchase_request->create_payload_from_cart($post_data,$post_id);
                
                
                $this->log_debug(
                    __('purchase payload: ' . wp_json_encode($payload))
                );
    
                $checkout_service = new Latitude_Checkout_Service();
                $response = $checkout_service->send_purchase_request($payload);
     
                if ($response == false) { 
                    return $this->update_post_meta_on_error($post_id, 'failed', "Purchase request was not valid. Please contact the merchant.") ;
                }
    
                if (is_array($response) && $response['result'] == 'failure') { 
                    $this->log_error(__( 'Error on Purchase Request API: ' . $response['response'] ));
                    return $this->update_post_meta_on_error($post_id, 'failed', 'Purchase request or merchant configuration does not look correct. Please contact the merchant.') ;
                }
    
               
                $rsp_body = $response['response'];
                $this->log_debug($rsp_body);
                if (!is_array($rsp_body)) { 
                    return $this->update_post_meta_on_error($post_id, 'failed', "Purchase request returned invalid data. Please contact the merchant.") ;
                } 
                  
                $result = $rsp_body['result']; 
                if ($result != 'pending') { 
                    $error_string = $rsp_body['error'];
                    if (empty($error_string)){
                        return $this->update_post_meta_on_error($post_id, $result, "Purchase request could not be verified. Please contact the merchant.") ;
                    } else {
                        return $this->update_post_meta_on_error($post_id, $result, "Purchase request failed. {$error_string}.") ;
                    }
                    
                } else { 

                    $redirecturl_nonce = wp_create_nonce( "redirecturl_nonce-{$post_id}" );
                    add_post_meta( $post_id, 'status', 'pending' );  
					add_post_meta( $post_id, 'posted', base64_encode(serialize($post_data)) );
					add_post_meta( $post_id, 'cart', base64_encode(serialize($cart)) );
					add_post_meta( $post_id, 'cart_hash', base64_encode(serialize($cart_hash)) );                    
                    add_post_meta( $post_id, 'merchant_id', $rsp_body['merchantId']);  
                    add_post_meta( $post_id, 'merchant_reference', $rsp_body['merchantReference']);  
                    add_post_meta( $post_id, 'amount', $rsp_body['amount'] );  
                    add_post_meta( $post_id, 'currency', $rsp_body['currency'] );  
                    add_post_meta( $post_id, 'redirect_url', $rsp_body['redirectUrl']); // to remove
                    add_post_meta( $post_id, 'redirecturl_nonce', $redirecturl_nonce); 

					add_post_meta( $post_id, 'chosen_shipping_methods', base64_encode(serialize($chosen_shipping_methods)) );
					add_post_meta( $post_id, 'shipping_packages', base64_encode(serialize($shipping_packages)) );

					add_post_meta( $post_id, 'customer_id', base64_encode(serialize($customer_id)) );
					add_post_meta( $post_id, 'order_vat_exempt', base64_encode(serialize($order_vat_exempt)) );
					add_post_meta( $post_id, 'currency', base64_encode(serialize($currency)) );
					add_post_meta( $post_id, 'prices_include_tax', base64_encode(serialize($prices_include_tax)) );
					add_post_meta( $post_id, 'customer_ip_address', base64_encode(serialize($customer_ip_address)) );
					add_post_meta( $post_id, 'customer_user_agent', base64_encode(serialize($customer_user_agent)) );
					add_post_meta( $post_id, 'customer_note', base64_encode(serialize($customer_note)) );
					add_post_meta( $post_id, 'payment_method', base64_encode(serialize($payment_method)) );
					add_post_meta( $post_id, 'shipping_total', base64_encode(serialize($shipping_total)) );
					add_post_meta( $post_id, 'discount_total', base64_encode(serialize($discount_total)) );
					add_post_meta( $post_id, 'discount_tax', base64_encode(serialize($discount_tax)) );
					add_post_meta( $post_id, 'cart_tax', base64_encode(serialize($cart_tax)) );
					add_post_meta( $post_id, 'shipping_tax', base64_encode(serialize($shipping_tax)) );
					add_post_meta( $post_id, 'total', base64_encode(serialize($total)) );
                    $this->process_payment($post_id);
                }
            }  
        }

        /**
         *  add error message on meta data
         *
         */
        private function update_post_meta_on_error($post_id, $result, $error_string) {
            add_post_meta( $post_id, 'status', $result );
            add_post_meta( $post_id, 'error', $error_string );
            $this->log_error($error_string); 
            return new WP_Error('create-order-error', $error_string ); 
        }

         /**
         *  process payment called in create order override 
         *
         */
        public function process_payment($order_id) {

            $this->log_info(
                __(
                    "Processing order ID : {$order_id}, using payment gateway: " .
                        LatitudeConstants::WC_LATITUDE_GATEWAY_NAME
                )
            );

            if ($order_id === -2) {
                $this->log_error('process_payment ...purchase request failed'); 
                # purchase request failed
                wp_send_json(array(
                    'result'	=> 'success',
                    'messages'	=> '<div class="woocommerce-error">There was a problem preparing your payment. Please try again.</div>'
                ));
            } elseif ($order_id === -1) {
                # failed to create pre order 
                $this->log_error('process_payment ...failed to create pre order '); 
                wp_send_json(array(
                    'result'	=> 'success',
                    'messages'	=> '<div class="woocommerce-error">There was a problem preparing your payment. Please try again.</div>'
                ));
            } elseif ($order_id > 0) {
                $this->log_debug('process_payment ...get_post_meta'); 
                // send verify purchase request here
                $quote = get_post($order_id); 
                $this->log_debug($quote); 
                $redirect_url = get_post_meta( $order_id, 'redirect_url', true );
                if (is_null($redirect_url) || empty($redirect_url)) {
                    $this->log_warning('process_payment ...redirect_url is empty '); 
                    wp_send_json(array(
                        'result'	=> 'success',
                        'messages'	=> '<div class="woocommerce-error">There was a problem preparing your payment. Please try again.</div>'
                    ));
                } else {
                    $this->log_info(
                        __(
                            'Redirect URL: ' .
                            $redirect_url
                        )
                    );

                    if (!is_ajax()) {
                        wp_safe_redirect($redirect_url);
                        exit();
                    }
                    wp_send_json([
                        'result' => 'success',
                        'redirect' => $redirect_url,
                    ]);  
                } 
            }
        }

        
        public function register_post_types() { 
			register_post_type( 'latitudecheckout_order', array(
				'labels' => array(
					'name' => __( 'Latitude Interest Free Orders' ),
					'singular_name' => __( 'Latitude Interest Free Order' ),
					'not_found' => __( 'No orders found.' ),
					'all_items' => __( 'View All' )
				),
				'supports' => array(
					'custom-fields'
				),
				'public' => true,
				'publicly_queriable' => false,
				'show_ui' => false, # Set to true to render Admin UI for this post type.
				'can_export' => false,
				'exclude_from_search' => true,
				'show_in_nav_menus' => false,
				'has_archive' => false,
				'rewrite' => false
			));
		}

        private function is_post_latitudecheckout_order($post) {
			if (is_numeric($post) && $post > 0) {
				$post = get_post( (int)$post );
			}

			if ($post instanceof WP_Post) {
				if (($post->post_type === 'latitudecheckout_order') ||
                ($post->post_title === 'Latitude Checkout Order')) {
					return true;
				}
			}

			return false;
		}
        
 
        /**
         *
         * Validates contents of the purchase_request to match current order.
         *
         */
        protected function is_valid_order_response($response, $order)
        {
            $error = '';
            if ($response['merchantId'] != $this->merchant_id) {
                $error = 'Failed to confirm Merchant ID.';
                return $error;
            }
            if ($response['merchantReference'] != $order->get_id()) {
                $error = 'Failed to confirm Order reference.';
                return $error;
            }
            if ($response['amount'] != $order->get_total()) {
                $error = 'Failed to confirm transaction amount.';
                return $error;
            }
            if ($response['currency'] != $order->get_currency()) {
                $error = 'Failed to confirm transaction urrency';
                return $error;
            }
            return $error;
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
        public function on_latitude_checkout_callback() {
            $this->log_debug('on_latitude_checkout_callback');
            if (is_admin()) {
                return;
            }

            if (empty($_GET)) {
                return;
            }

            $quote = null;
            if (array_key_exists('post_type', $_GET) && array_key_exists('p', $_GET)) {
                if ($_GET['post_type'] == 'latitudecheckout_order' && is_numeric($_GET['p'])) {
                    $quote = get_post( (int)$_GET['p'] );
                }
            }  
            if (is_null($quote)) {
                $quote = get_post(); 
            } 
            $this->log_debug($quote);
            if (!$this->is_post_latitudecheckout_order($latitudecheckout_post)) {
                $this->log_debug('this is NOT a valid latitudecheckout_order post');
            } else {
                $this->log_debug('this is a valid latitudecheckout_order post');
            }

            if (array_key_exists('gatewayReference', $_GET)) {
                $gatewayReference = $_GET['gatewayReference'];
            }
            if (array_key_exists('transactionReference', $_GET)) {
                $transactionReference = $_GET['transactionReference'];
            }
            if (array_key_exists('merchantReference', $_GET)) {
                $merchantReference = $_GET['merchantReference'];
            }
            $this->log_debug(
                "merchantReference: {$merchantReference}, transactionReference: {$transactionReference}, gatewayReference: {$gatewayReference}"
            );
            if (
                empty($gatewayReference) ||
                empty($transactionReference) ||
                empty($merchantReference)
            ) { 
                $this->update_and_trash_post( $quote->ID, "Missing parameters on callback.");
            }

            $payload = [
                'gatewayReference' => $gatewayReference,
                'transactionReference' => $transactionReference,
                'merchantReference' => $merchantReference,
            ];

            $this->log_debug('verify_purchase ... ');
            $this->log_debug($payload); 
            
            $checkout_service = new Latitude_Checkout_Service();
            $response = $checkout_service->verify_purchase_request($payload);

            if ($response === false) { 
                $this->update_and_trash_post( $quote->ID, "Verify Purchase API failed.");  
            } elseif (is_array($response)) {
                $rsp_body = $response['response']; 
                $this->log_debug($rsp_body);
                $result = $rsp_body['result'];
                $transactionType = $rsp_body['transactionType'];
                $promotionReference = $rsp_body['promotionReference'];
                $message = $rsp_body['message'];

                if ($result == 'completed') {

                    $orderReference = [
                        'gatewayReference' => $gatewayReference,
                        'transactionReference' => $transactionReference,
                        'merchantReference' => $merchantReference,
                        'promotionReference' => $promotionReference,
                        'transactionType' => $transactionType
                    ];

                    $order = $this->create_wc_order($quote->ID, $orderReference);
                    
                    if (!is_wp_error($order)) {
                        do_action( 'woocommerce_checkout_order_processed', $quote->ID, $posted, $order );
                        if ($transactionType == 'sale') {
                            $this->log_info(
                                "WooCommerce Order #{$merchantReference} transaction is \"completed\"."
                            ); 
                            $order->add_order_note(
                                sprintf(
                                    __(
                                        'Payment approved. Transaction reference: %s, Gateway reference: %s',
                                        'woo_latitudecheckout'
                                    ),
                                    $transactionReference,
                                    $gatewayReference
                                )
                            );
                            $order->payment_complete($transactionReference);
                        } else {
                            // assume returned $transactionType == 'authorisation'  
                            $this->log_info(
                                "WooCommerce Order #{$merchantReference} transaction is   \"{$transactionType}\"."
                            ); 
                            $order->add_order_note(
                                sprintf(
                                    __(
                                        'Payment under %s. Transaction reference: %s, Gateway reference: %s',
                                        'woo_latitudecheckout'
                                    ),
                                    $transactionType,
                                    $transactionReference,
                                    $gatewayReference
                                )
                            );
                            $order->update_status( 'on-hold' ); 
                        }   
                        $order->save(); 
                        if (wp_redirect( $order->get_checkout_order_received_url() )) {
                            exit;
                        }
                    }
                    return $order;

                } else {
                    $error_string = __ ("Failed to verify purchase. {$message}"); 
                    $this->log_warning("Failed to verify purchase for quote #{$quote->ID}. {$message}");
                    $this->update_and_trash_post( $quote->ID, $error_string);
                }
            } else { 
                $this->update_and_trash_post( $quote->ID, 'Your payment could not be processed. Please try again.');
            }  
            return $false;
        }

        //create_wc_order_from_afterpay_quote_3_6
        private function create_wc_order($post_id, $orderReference) {
            $checkout = WC()->checkout;
            //
            $data = $this->unserialize_base64_decode(get_post_meta( $post_id, 'posted', true ));
            $cart = $this->unserialize_base64_decode(get_post_meta( $post_id, 'cart', true ));
            $cart_hash = $this->unserialize_base64_decode(get_post_meta( $post_id, 'cart_hash', true ));
            $chosen_shipping_methods = $this->unserialize_base64_decode(get_post_meta( $post_id, 'chosen_shipping_methods', true ));
			$shipping_packages = $this->unserialize_base64_decode(get_post_meta( $post_id, 'shipping_packages', true ));
        	$customer_id = $this->unserialize_base64_decode(get_post_meta( $post_id, 'customer_id', true ));
			$order_vat_exempt = $this->unserialize_base64_decode(get_post_meta( $post_id, 'order_vat_exempt', true ));
			$currency = $this->unserialize_base64_decode(get_post_meta( $post_id, 'currency', true ));
			$prices_include_tax = $this->unserialize_base64_decode(get_post_meta( $post_id, 'prices_include_tax', true ));
			$customer_ip_address = $this->unserialize_base64_decode(get_post_meta( $post_id, 'customer_ip_address', true ));
			$customer_user_agent = $this->unserialize_base64_decode(get_post_meta( $post_id, 'customer_user_agent', true ));
			$customer_note = $this->unserialize_base64_decode(get_post_meta( $post_id, 'customer_note', true ));
			$payment_method = $this->unserialize_base64_decode(get_post_meta( $post_id, 'payment_method', true ));
			$shipping_total = $this->unserialize_base64_decode(get_post_meta( $post_id, 'shipping_total', true ));
			$discount_total = $this->unserialize_base64_decode(get_post_meta( $post_id, 'discount_total', true ));
			$discount_tax = $this->unserialize_base64_decode(get_post_meta( $post_id, 'discount_tax', true ));
			$cart_tax = $this->unserialize_base64_decode(get_post_meta( $post_id, 'cart_tax', true ));
			$shipping_tax = $this->unserialize_base64_decode(get_post_meta( $post_id, 'shipping_tax', true ));
			$total = $this->unserialize_base64_decode(get_post_meta( $post_id, 'total', true ));    

            try {

				# Force-delete the quote item. This will make its ID available to be used as the WC_Order ID. 
				wp_delete_post( $post_id, true ); 
	            $order = new WC_Order();

	            $fields_prefix = array(
	                'shipping' => true,
	                'billing'  => true,
	            );

	            $shipping_fields = array(
	                'shipping_method' => true,
	                'shipping_total'  => true,
	                'shipping_tax'    => true,
	            );
	            foreach ( $data as $key => $value ) {
	                if ( is_callable( array( $order, "set_{$key}" ) ) ) {
	                    $order->{"set_{$key}"}( $value );
	                } elseif ( isset( $fields_prefix[ current( explode( '_', $key ) ) ] ) ) {
	                    if ( ! isset( $shipping_fields[ $key ] ) ) {
	                        $order->update_meta_data( '_' . $key, $value );
	                    }
	                }
	            }

	            $order->set_created_via( 'checkout' );
	            $order->set_cart_hash( $cart_hash );
	            $order->set_customer_id( $customer_id );
	            $order->add_meta_data( 'is_vat_exempt', $order_vat_exempt );
	            $order->set_currency( $currency );
	            $order->set_prices_include_tax( $prices_include_tax );
	            $order->set_customer_ip_address( $customer_ip_address );
	            $order->set_customer_user_agent( $customer_user_agent );
	            $order->set_customer_note( $customer_note );
	            $order->set_payment_method( $payment_method );
	            $order->set_shipping_total( $shipping_total );
	            $order->set_discount_total( $discount_total );
	            $order->set_discount_tax( $discount_tax );
	            $order->set_cart_tax( $cart_tax );
	            $order->set_shipping_tax( $shipping_tax );
	            $order->set_total( $total );

                $order->payment_complete($transactionReference);
                $order->add_meta_data( 'gatewayReference',  $orderReference['gatewayReference'] );
                $order->add_meta_data( 'transactionReference', $orderReference['transactionReference'] );
                $order->add_meta_data( 'promotionReference', $orderReference['promotionReference'] );
                $order->add_meta_data(  'transactionType', $orderReference['transactionType'] );

	            $checkout->create_order_line_items( $order, $cart );
	            $checkout->create_order_fee_lines( $order, $cart );
	            $checkout->create_order_shipping_lines( $order, $chosen_shipping_methods, $shipping_packages );
	            $checkout->create_order_tax_lines( $order, $cart );
	            $checkout->create_order_coupon_lines( $order, $cart );
 
	            $GLOBALS['latitudecheckout_order_id'] = $post_id;

	            do_action( 'woocommerce_checkout_create_order', $order, $data );

	            $order_id = $order->save();

	            do_action( 'woocommerce_checkout_update_order_meta', $order_id, $data );

	            # Clear globals after use, if not already cleared. 
	            if (isset($GLOBALS['latitudecheckout_order_id'])) {
					unset($GLOBALS['latitudecheckout_order_id']);
				}

	            return $order;
	        } catch ( Exception $e ) {
	            return new WP_Error( 'checkout-error', $e->getMessage() );
	        }

        }

        public function filter_new_order_data( $order_data ) {
			if (array_key_exists('latitudecheckout_order_id', $GLOBALS) && is_numeric($GLOBALS['latitudecheckout_order_id']) && $GLOBALS['latitudecheckout_order_id'] > 0) {
				$order_data['import_id'] = (int)$GLOBALS['latitudecheckout_order_id'];
				unset($GLOBALS['latitudecheckout_order_id']);
			}
			return $order_data;
		}

        private function unserialize_base64_decode($string) {
            return unserialize(base64_decode($string));
        }

        private function update_and_trash_post($post_id, $error_string) {
            $this->log_error(
                __( "Deleting post #{$post_id} on error: " . $error_string)
            );

            update_post_meta( $post_id, 'status', 'failed' );
            if (function_exists('wp_trash_post')) {
                wp_trash_post( $post_id );
            } 

            # Store an error notice and redirect the customer back to the checkout.
            wc_add_notice(__($error_string, 'woo_latitudecheckout'), 'error');
            wp_safe_redirect( wc_get_checkout_url() ); 
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
