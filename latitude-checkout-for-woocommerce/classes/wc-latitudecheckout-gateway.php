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

use Environment_Settings as LatitudeConstants; 
/**
 * The core plugin class
 *
 * This is the Latitude Checkout - WooCommerce Payment Gateway Class.
 */
if (!class_exists('WC_LatitudeCheckout_Gateway')) {
    class WC_LatitudeCheckout_Gateway extends WC_Payment_Gateway
    { 

        /**
         * Protected static variable
         *
         *
         * @var     WC_LatitudeCheckout_Gateway|null     $instance           Latitude Checkout Payment Gateway Object Instance. Defaults to null.
         *
         */

        protected static $instance = null;

       	/**
		 * Reference to API class.
		 *
		 * @var Latitude_Chekout_API $api_service
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

        protected static $log = null, $log_enabled = null;

 

        /**
         * Protected variables.
         *
         *
         * @var     string     $merchant_id         Merchant Unique ID configuration. Set at the admin page.
         * @var     string     $merchant_secret     Merchant Secret Key configuration. Set at the admin page. 
         *
         */
        protected $merchant_id, $merchant_secret;

     

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
            $this->title = 'Latitude Interest Free';
            $this->method_title = $this->title;
            $this->method_name =$this->title; 
            $this->method_description = sprintf(  __( 'Use %s as payment method for WooCommerce orders.', 'woo_latitudecheckout' ), $this->title );

            $this->icon = apply_filters('woocommerce_gateway_icon', 10, 2);
            $this->has_fields = true; // needed to be true for customizing payment fields
            
          
            $this->init_form_fields();
            $this->init_settings(); 

            $this->api_service = new Latitude_Chekout_API();
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
		public function process_admin_options() {
			parent::process_admin_options(); 

            if (array_key_exists('widget_content', $this->settings)) { 
                $result = ( json_decode( $this->settings['widget_content'], true ) == NULL ) ? false : true ;
                if ( $result  === false )
                {
                    WC_Admin_Settings::add_error('Error: Invalid widget content.');  
                }
            } 
            if (array_key_exists('merchant_secret', $this->settings) && 
                array_key_exists('merchant_id', $this->settings)) 
            {
               if(empty($this->settings['merchant_secret']) || empty($this->settings['merchant_id']))
               {
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
            $test_mode = true;
            if (array_key_exists('test_mode', $this->settings)) {
                $test_mode = 'yes' === $this->settings['test_mode'];
            }
            return $test_mode;
        }

        public function get_api_settings() 
        {
            return $this->get_test_mode() ? "test" : "prod"; 
        }

        public function get_payment_gateway_id() 
        {
            return $this->id;
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
                plugin_dir_url( __DIR__ ) . 'assets/js/woocommerce.js',
                ['jquery']
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
            if ($gateway_id != $this->id) {
                return $icon;
            }

            $currency = get_woocommerce_currency();
            if (!in_array($currency, LatitudeConstants::ALLOWED_CURRENCY)) {
                return;
            }

            $icon_url = LatitudeConstants::location_settings[$currency]["icon_url"];
            $icon_alt_text = LatitudeConstants::location_settings[$currency]["icon_alt_text"]; 

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
                plugin_dir_url( __DIR__ ). 'assets/css/latitude.css'
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
            if ( $current_payment_method == $this->id ) {
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
            <script>
                function reloadScript() {
                        var curr = document.createElement("script");
                        curr.type = "text/javascript";
                        curr.async = true;
                        curr.src = '<?php echo $this->get_payment_fields_src(); ?>';
                        var scr = document.getElementsByTagName("script")[0];
                        scr.parentNode.insertBefore(curr, scr);
                    }
            </script>
            <?php
           
            wp_enqueue_script(
                'latitude_paymentfield_js', 
                plugin_dir_url( __DIR__ ). 'assets/js/woocommerce.js'
                
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
            $env = LatitudeConstants::api_settings[$this->get_api_settings()]["payment_fields_url"];
            $url = __(
                   $env . '/assets/content.js?platform=woocommerce&merchantId=' .  $this->get_merchant_id()
                    );
            return $url;
        }

        /**
         * Returns the asset url to display widget at product page.
         *
         */
        protected function get_widget_asset_src()
        {
            $env = LatitudeConstants::api_settings[$this->get_api_settings()]["widgets_url"];
            $url = __(
                $env . '/assets/content.js?platform=woocommerce&merchantId=' .  $this->get_merchant_id()
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
            $this->log_info( __( "Processing payment using {$this->id} payment method." ) );
            if (!$order_id) {
                $this->log_error( 'Order ID cannot be null when processing payment.' );
                return;
            }   
            $response = $this->api_service->purchase_request($order_id);
            $this->log_info( __( "purchase_request result: "  . json_encode($response)));
            return $response;
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
                $gateway = new WC_LatitudeCheckout_Gateway();
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