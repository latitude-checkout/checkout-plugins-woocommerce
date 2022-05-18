<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @since      1.0.0
 *
 * @package    latitude-checkout-for-woocommerce
 * @subpackage latitude-checkout-for-woocommerce/includes
 */

use Latitude_Checkout_Environment_Settings;

/**
 * The core payment gateway class
 *
 * This is the Latitude Checkout - WooCommerce Payment Gateway Class.
 */
if (!class_exists('WC_Latitude_Checkout_Gateway')) {
    class WC_Latitude_Checkout_Gateway extends WC_Payment_Gateway
    {
        const MERCHANT_ID = 'merchant_id';
        const MERCHANT_SECRET = 'merchant_secret';
        const ENABLED = 'enabled';
        const TEST_MODE = 'test_mode';
        const DEBUG_MODE = 'debug_mode';
        const ADVANCED_CONFIG = 'advanced_config';

        const ERROR = 'error';
        const MESSAGE = 'message';
        const BODY = 'body';

        /**
         * Protected static variable
         *
         *
         * @var     WC_Latitude_Checkout_Gateway|null     $instance           Latitude Checkout Payment Gateway Object Instance. Defaults to null.
         *
         */

        protected static $instance = null;

        /**
         * Reference to API class.
         *
         * @var Latitude_Checkout_API $api_service
         */
        public $api_service;

        /**
         * Protected static variable
         *
         *
         * @var     WC_Logger|null                      $log                WC_logger object instance. Defaults to null.
         * @var		bool|null			                $log_enabled	    Whether or not logging is enabled. Defaults to null.
         *
         */

        protected static $log = null;
        protected static $log_enabled = null;

 

        /**
         * Protected variables.
         *
         *
         * @var     string     $merchant_id         Merchant Unique ID configuration. Set at the admin page.
         * @var     string     $merchant_secret     Merchant Secret Key configuration. Set at the admin page.
         *
         */
        protected $merchant_id;
        protected $merchant_secret;

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
            $this->id = 'latitudecheckout';
            $this->title = Latitude_Checkout_Environment_Settings::get_gateway_title();
            $this->method_title = $this->title;
            $this->method_name =$this->title;
            $this->method_description = sprintf(__('Use %s as payment method for WooCommerce orders.', 'woo_latitudecheckout'), $this->title);
            $this->icon = apply_filters('woocommerce_gateway_icon', 10, 2);
            $this->has_fields = true; // needed to be true for customizing payment fields
            $this->init_form_fields();
            $this->init_settings();
            $this->api_service = new Latitude_Checkout_API();

            $this->supports = array("refunds");

            // check whether gateway actions are already added
            if (!has_action("woocommerce_update_options_payment_gateways_{$this->id}")) {
                /*
                * Actions
                */
                add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
                add_action("woocommerce_update_options_payment_gateways_{$this->id}", [$this, 'process_admin_options'], 10, 0);
                add_action("woocommerce_receipt_{$this->id}", [$this, 'receipt_page'], 10, 1);
                add_action('woocommerce_admin_order_data_after_order_details', [$this,'display_order_data_in_admin',]);
                add_action('woocommerce_before_checkout_form', [$this, 'add_checkout_custom_style'], 10, 2);
                add_action('woocommerce_single_product_summary', [$this, 'get_widget_data'], 10, 2);
                add_action('woocommerce_after_checkout_validation', [ $this, 'validate_checkout_fields'], 10, 2);

                /*
                * Order management actions
                */
                add_action('woocommerce_order_actions', array($this, 'add_order_mgmt_actions'));
                
                // Capture an order.
                add_action("woocommerce_order_action_{$this->id}_process_capture", array( $this, 'process_capture' ), 10, 1);

                // Void order.
                add_action("woocommerce_order_action_{$this->id}_process_void", array( $this, 'process_void' ), 10, 1);

                /*
                * Filters
                */
                add_filter('woocommerce_gateway_icon', [$this, 'filter_gateway_icon'], 10, 2);
                add_filter('woocommerce_order_button_text', [$this, 'filter_place_order_button_text'], 10, 1);
                add_filter('woocommerce_endpoint_order-pay_title', [$this, 'filter_order_pay_title'], 10, 2);
            }
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
         *  Default values for the plugin's Admin Form Fields
         */
        public function init_form_fields()
        {
            include "{$this->include_path}/admin-form.php";
        }

        /**
         * Adds/Updates admin settings - needed to overload explicitly to update admin settings in some shops
         *
         * Note:	Hooked onto the "woocommerce_update_options_payment_gateways_" Action.
         *
         */
        public function process_admin_options()
        {
            parent::process_admin_options();

            if (array_key_exists(self::ADVANCED_CONFIG, $this->settings)) {
                $is_valid_json = json_decode($this->settings[self::ADVANCED_CONFIG], true) != null ;
                if (!$is_valid_json) {
                    WC_Admin_Settings::add_error('Error: Please enter valid JSON for Advanced Config.');
                }
            }
            if (array_key_exists(self::MERCHANT_SECRET, $this->settings) &&
                array_key_exists(self::MERCHANT_ID, $this->settings)) {
                if (empty($this->settings[self::MERCHANT_SECRET]) || empty($this->settings[self::MERCHANT_ID])) {
                    WC_Admin_Settings::add_error('Error: Merchant details cannot be empty.');
                }
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
            return $this->get_option(self::MERCHANT_ID);
        }

        /**
         * Get the Merchant Secret Key from our user settings.
         */
        public function get_secret_key()
        {
            return $this->get_option(self::MERCHANT_SECRET);
        }

        /**
         * Returns true if the Test Mode Enabled from our user settings, otherwise returns false.
         */
        public function is_test_mode()
        {
            return $this->get_option(self::TEST_MODE) === 'yes';
        }

        /**
         * Returns true if the Debug Mode Enabled from our user settings, otherwise returns false.
         */
        public function is_debug_mode()
        {
            return $this->is_test_mode() || $this->get_option(self::DEBUG_MODE) === 'yes';
        }
  
        public function get_payment_gateway_id()
        {
            return $this->id;
        }

        /**
         * Note: Hooked onto the "wp_enqueue_scripts" Action
         *
         */
        public function enqueue_scripts()
        {
            /**
             * Enqueue JS for updating  place order button text on payment method change
             */

            wp_enqueue_script(
                'latitude_payment_fields_js',
                plugin_dir_url(__DIR__) . 'assets/js/latitude-payment-fields.js',
                ['jquery']
            );
        }
 
        /**
         * Get the Widget settings from our user settings.
         */
        public function get_widget_data()
        {
            echo '<div id="latitude-banner-container"></div>';
            $widgetData = $this->get_option(self::ADVANCED_CONFIG);
            $obj = json_decode($widgetData, true);
            $product = wc_get_product();
            $category = get_the_terms($product->id, 'product_cat');
            wp_enqueue_script(
                'latitude_widget_js',
                plugin_dir_url(__DIR__). 'assets/js/woocommerce.js',
                ['jquery']
            );
            wp_localize_script(
                'latitude_widget_js',
                'latitude_widget_js_vars',
                [
                    'page' => 'product',
                    'container' => 'latitude-banner-container',
                    'widgetSettings' => $obj,
                    'merchantId' => $this->get_merchant_id(),
                    'currency' => Latitude_Checkout_Environment_Settings::get_base_currency(),
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $category[0]->name,
                    'price' => floatval(wc_get_price_including_tax($product)),
                    'sku' => $product->sku,
                    'assetUrl' => $this->get_content_src(),
                ]
            );
        }
 

        /**
         *
         * Hooked onto the "woocommerce_gateway_icon" filter.
         *
         */
        public function filter_gateway_icon($icon, $gateway_id)
        {
            if ($gateway_id != $this->id) {
                return $icon;
            }
  
            $icon_url = Latitude_Checkout_Environment_Settings::get_icon_url();
            $icon_alt_text = Latitude_Checkout_Environment_Settings::get_gateway_title();
            ob_start(); ?><img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_html($icon_alt_text); ?>" class="checkout-logo__latitude" /><?php return ob_get_clean();
        }

        /**
         * Styles for Latitude interest free
         *
         */
        public function add_checkout_custom_style()
        {
            wp_enqueue_style(
                'latitude_checkout-styles',
                plugin_dir_url(__DIR__). 'assets/css/latitude.css'
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
            );
            if ($current_payment_method == $this->id) {
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
            ?>   
            <div id="latitude-payment--main"> 
            <div style="display: flex !important;">
                <p style="margin-top: 14px !important; margin-bottom: 14px !important">
                <strong>Enjoy Now. Pay Later.</strong>
            </div>
            <p style="margin-top: 14px !important; margin-bottom: 14px !important; text-align:left">
                Flexible Interest Free Plans to suit your needs
            </p>
            <p style="margin-top: 14px !important; margin-bottom: 14px !important; font-size: 12px; text-align:left">
                You will be redirected to Latitude complete your order
            </p>
            </div>  
            <div id="latitude-payment--footer"></div> 
            <?php

            wp_enqueue_script(
                'latitude_paymentfield_banner_js',
                plugin_dir_url(__DIR__). 'assets/js/woocommerce.js',
                ['jquery']
            );

            $order_data = $this->get_session_order_data();
            wp_localize_script(
                'latitude_paymentfield_banner_js',
                'latitude_widget_js_vars',
                [
                    'page' => 'checkout',
                    'container' => [
                        'footer' => 'latitude-payment--footer',
                        'main' => 'latitude-payment--main',
                    ],
                    'merchantId' => $this->get_merchant_id(),
                    'currency' => Latitude_Checkout_Environment_Settings::get_base_currency(),
                    'assetUrl' => $this->get_content_src(),
                    'widgetSettings' => '',
                    'checkout' => [
                            'shippingAmount' => $order_data['shippingAmount'],
                            'taxAmount' => $order_data['taxAmount'],
                            'total' => $order_data['total'],
                    ],
                ]
            );
        }
 
        /**
         * Retrieves cart session totals
         *
         */
        private function get_session_order_data()
        {
            $cart = WC()->cart;
            $total_tax = max(0, $cart->get_total_tax());
            $total_shipping = max(0, $cart->shipping_total + $cart->shipping_tax_total);
            return array(
                "total" =>  floatval($cart->total),
                "shippingAmount" => floatval(number_format($total_shipping, 2, '.', '')),
                "taxAmount" => floatval(number_format($total_tax, 2, '.', '')),
            );
        }


        /**
         * Returns the asset url to display widget at product page.
         *
         */
        protected function get_content_src()
        {
            $env = Latitude_Checkout_Environment_Settings::get_content_url($this->is_test_mode());
            $url = __(
                $env . '/assets/content.js?platform=woocommerce&merchantId=' .  $this->get_merchant_id()
            );
            return $url;
        }

        /**
         *
         * Hooked onto the "woocommerce_after_checkout_validation" filter.
         *
         */
        public function validate_checkout_fields($fields, $errors)
        {
            if (preg_match('/\\d/', $fields[ 'billing_first_name' ]) || preg_match('/\\d/', $fields[ 'billing_last_name' ])) {
                $errors->add('validation', 'Your first or last name contains a number.');
                return;
            }
            //Add additional field validations here when needed
        }

        /**
         *
         * Hooked onto the "capture_payment" filter, adds capture option in order actions
         *
         */
        public function add_order_mgmt_actions($actions)
        {
            global $theorder;

            if (!is_object($theorder)) {
                return $actions;
            }

            if ($theorder->get_payment_method() != $this->id) {
                return $actions;
            }

            if (!$theorder->has_status(Latitude_Checkout_Constants::WC_STATUS_ON_HOLD)) {
                return $actions;
            }

            $actions["{$this->id}_process_capture"] = __('Capture via '. $this->method_title, $this->id);
            $actions["{$this->id}_process_void"] = __('Void / Cancel via '. $this->method_title, $this->id);
            
            return $actions;
        }

        /**
         * Default process payment
         *
         */
        public function process_payment($order_id)
        {
            $this->log_info(__("Processing payment using {$this->id} payment method."));
            if (!$order_id) {
                $this->log_error('Order ID cannot be null when processing payment.');
                return;
            }
            $response = $this->api_service->purchase_request($order_id);
            $this->log_info(__("purchase_request result: "  . json_encode($response)));
            return $response;
        }

        /**
        * Process capture
        */
        public function process_capture($order)
        {
            $order_id = $order->get_id();

            $this->log_info(__("Initiating capture {$order_id}"));

            try {
                if ($order->get_payment_method() != $this->id) {
                    return false;
                }

                $capture_response = $this->api_service->capture_request($order_id, $reason);
    
                if ($capture_response[self::ERROR]) {
                    throw new Exception(__($capture_response[self::MESSAGE]));
                }

                $capture_response_body = $capture_response[self::BODY];

                $order->add_order_note("Information from Gateway: ". $this->to_pretty_json($capture_response_body));
                $order->add_order_note("Capture Approved for {$order->get_total()} {$order->get_currency()}");

                $order->payment_complete();

                return $this->handle_capture_success($capture_response_body);
            } catch (\Exception $ex) {
                $errorMessage = $ex->getMessage();
                return $this->handle_capture_error($order, $errorMessage);
            }
        }

        private function handle_capture_success($body)
        {
            $this->log_info(__METHOD__. " ". json_encode($body));
            return true;
        }

        private function handle_capture_error($order, $message)
        {
            $this->log_info(__METHOD__. " ". $message);

            $order->add_order_note("Capture failed. ". $message);
            $order->save();
        }


        /**
        * Process void
        */
        public function process_void($order)
        {
            $order_id = $order->get_id();

            $this->log_info(__("Initiating void {$order_id}"));

            try {
                if ($order->get_payment_method() != $this->id) {
                    return false;
                }

                $void_response = $this->api_service->void_request($order_id, $reason);
    
                if ($void_response[self::ERROR]) {
                    throw new Exception(__($void_response[self::MESSAGE]));
                }

                $void_response_body = $void_response[self::BODY];

                $order->add_order_note("Information from Gateway: ". $this->to_pretty_json($void_response_body));
                $order->add_order_note("Void Completed for {$order->get_total()} {$order->get_currency()}");

                $order->update_status(Latitude_Checkout_Constants::WC_STATUS_CANCELLED);

                return $this->handle_void_success($void_response_body);
            } catch (\Exception $ex) {
                $errorMessage = $ex->getMessage();
                return $this->handle_void_error($order, $errorMessage);
            }
        }

        private function handle_void_success($body)
        {
            $this->log_info(__METHOD__. " ". json_encode($body));
            return true;
        }

        private function handle_void_error($order, $message)
        {
            $this->log_info(__METHOD__. " ". $message);

            $order->add_order_note("Void failed. ". $message);
            $order->save();
        }

        /**
         * Default process refund
         */
        public function process_refund($order_id, $amount = null, $reason = '')
        {
            $this->log_info(__("Initiating refund {$order_id}"));

            $order = wc_get_order($order_id);

            try {
                if ($order->get_payment_method() != $this->id) {
                    return false;
                }

                $refund_response = $this->api_service->refund_request($order_id, $amount, $reason);
    
                if ($refund_response[self::ERROR]) {
                    throw new Exception(__($refund_response[self::MESSAGE]));
                }

                $refund_response_body = $refund_response[self::BODY];

                $order->add_order_note("Information from Gateway: ". $this->to_pretty_json($refund_response_body));
                $order->add_order_note("Refund Approved for {$amount} {$order->get_currency()}");

                return $this->handle_refund_success($refund_response_body);
            } catch (\Exception $ex) {
                $errorMessage = $ex->getMessage();
                $order->add_order_note("Refund Failed. ". $errorMessage);
                return $this->handle_refund_error($errorMessage);
            }

            return false;
        }

        private function to_pretty_json($value)
        {
            return implode(', ', array_map(
                function ($v, $k) {
                    return sprintf("\n %s: %s", $k, $v);
                },
                $value,
                array_keys($value)
            ));
        }

        private function handle_refund_error($message)
        {
            $this->log_info(__METHOD__. " ". $message);
            
            return new WP_Error(
                "latitude-checkout-refund-failed",
                __("Could not process refund. ". $message),
                null
            );
        }

        private function handle_refund_success($body)
        {
            $this->log_info(__METHOD__. " ". json_encode($body));
            return true;
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
            $order = $this->get_valid_order($order_id);
            if (is_null($order)) {
                return;
            }

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
         * Validates the $order_id and returns the valid order or null
         *
         */
        public function get_valid_order($order_id)
        {
            $order = wc_get_order($order_id);
            if (is_null($order) || !$order) {
                $this->log_error(
                    __('Invalid or non-existent order id:  ' . $order_id)
                );
                return null;
            }
            return $order;
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
                $is_pending = $order->has_status(Latitude_Checkout_Constants::WC_STATUS_PENDING);
            } else {
                $this->log_debug("order status: {$order->status}");
                if ($order->status == Latitude_Checkout_Constants::WC_STATUS_PENDING) {
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
            
            $gatewayRef =  $order->get_meta(Latitude_Checkout_Constants::GATEWAY_REFERENCE);
            $transactionRef = $order->get_meta(Latitude_Checkout_Constants::TRANSACTION_REFERENCE);
            $promotionRef = $order->get_meta(Latitude_Checkout_Constants::PROMOTION_REFERENCE);
            $transType = $order->get_meta(Latitude_Checkout_Constants::TRANSACTION_TYPE);
            if (empty($gatewayRef) && empty($transactionRef) && empty($promotionRef) && empty($transType)) {
                return;
            } ?> 
             <p class="form-field form-field-wide"> <br>
                <div class="latitude_payment_details">
                <h3><?php esc_html_e(
                'Latitude Interest Free Payment Details',
                'woo_latitudecheckout'
            ); ?></h3>
                    <?php echo '<p><strong>' .
                        __('Gateway Reference') .
                        ': </strong><br>' .
                        $gatewayRef .
                        '<br></p>'.
                        '<p><strong>'.
                                __('Transaction Reference') .
                                ': </strong><br>' .
                                $transactionRef .
                                '<br></p>'.
                        '<p><strong>' .
                                __('Promotion Reference') .
                                ': </strong><br>' .
                                $promotionRef .
                                '<br></p>'.
                        '<p><strong>'.
                                __('Transaction Type') .
                                ': </strong><br>' .
                                $order->get_meta(Latitude_Checkout_Constants::TRANSACTION_TYPE) .
                                '<br></p>'
                    ?>
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
                $gateway = new WC_Latitude_Checkout_Gateway();
                $settings_key = $gateway->get_option_key();
                $settings = get_option($settings_key);

                if (array_key_exists(self::TEST_MODE, $settings)) {
                    self::$log_enabled = $settings[self::TEST_MODE] == 'yes';
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
