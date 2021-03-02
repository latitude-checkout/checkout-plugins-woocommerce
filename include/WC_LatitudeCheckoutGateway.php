<?php
 
 use Constants as LatitudeConstants; 
 
/**
 * This is the Latitude Checkout - WooCommerce Payment Gateway Class.
 */
if (!class_exists('WC_LatitudeCheckoutGateway')) {

    

    class WC_LatitudeCheckoutGateway extends WC_Payment_Gateway   {

		/**
		 * Public static variables.
		 * 
		 */
        public static $log = null, $log_enabled = null;

		/**
		 * $version	    A reference to the plugin version, which will match
		 *								the value in the plugin comments.
		 */
		public static $version = LatitudeConstants::WC_LATITUDE_GATEWAY_VERSION;

            
    	/**
		 * Protected static variables.
		 * 
		 */
        protected static $instance = null;  

    	/**
		 * Protected variables.
		 * 
		 */
        protected $merchant_id, $merchant_secret, $test_mode, $payment_request = null;          
        
        private $include_path; 
     	/**
		 * Class Constructor
		 * 
		 */       
        public function __construct() { 
    
            $this->include_path = WC_LATITUDE_GATEWAY__PLUGIN_DIR . 'include';

            $this->id = LatitudeConstants::WC_LATITUDE_GATEWAY_ID;   
            $this->method_title =  __(  LatitudeConstants::WC_LATITUDE_GATEWAY_NAME, 'woo_latitudecheckout');
            $this->method_description = __( 'Use Latitude as payment method for WooCommerce orders.', 'woo_latitudecheckout'); 
            $this->icon = apply_filters('woocommerce_gateway_icon', 10, 2 );  
            $this->has_fields = true; // in case you need a customform
            $this->title =  _ ( LatitudeConstants::WC_LATITUDE_GATEWAY_NAME, 'woo_latitudecheckout');     

            $this->init_form_fields();
            $this->init_settings(); 
            $this->refresh_configuration();
            $this->merchant_id = $this->get_option('merchant_id');
            $this->merchant_secret = $this->get_option('merchant_secret');
            $this->test_mode = 'yes' === $this->get_option( 'testmode' ); 
            self::$log_enabled = $this->test_mode;
 
            $this->payment_request = new Latitude_Payment_Request;
        }  

        /**
		 * Instantiate the class if no instance exists. Return the instance.
		 * 
		 */
		public static function getInstance()
		{
			if (is_null(self::$instance)) {
				self::$instance = new self;
			}
			return self::$instance;
        } 
 
        /**
         * Adds the Latitude Checkout Payments Gateway to WooCommerce
         * 
         */ 
        public function add_latitudecheckoutgateway( $gateways ) {
            $gateways[] = 'WC_LatitudeCheckoutGateway';  
            return $gateways;
        } 

        
        /**
        *  Default values for the plugin's Admin Form Fields
        */       
        public function init_form_fields(){ 
            include "{$this->include_path}/Admin_Form_Fields.php";
        }    


        /**
		 * Refresh cached configuration to ensure properties are up to date 
		 *
		 * Note:	Hooked onto the "woocommerce_update_options_payment_gateways_{$gateway->id}" Action.
		 * 
		 */
		public function refresh_configuration() {
			if (array_key_exists('merchant_id', $this->settings)) {
				$this->merchant_id = $this->settings['merchant_id'];
            }
            if (array_key_exists('merchant_secret', $this->settings)) {
				$this->merchant_id = $this->settings['merchant_secret'];
            } 
			if (array_key_exists('testmode', $this->settings)) {
                $this->test_mode = ($this->settings['test_mode'] == 'yes');
                self::$log_enabled = $this->test_mode;
			} 
        }
        
       /**
		 *
		 * Hooked onto the "woocommerce_gateway_icon" filter.
		 *  
		 */
        function filter_latitude_gateway_icon( $icon, $gateway_id ) {
    
            if ($gateway_id != LatitudeConstants::WC_LATITUDE_GATEWAY_ID) {
				return $icon;
            } 

            $icon_url = LatitudeConstants::AU_ICON_URL;
            $icon_alt_text = "Latitude Interest Fee";
            if ( 'NZD' == get_woocommerce_currency() ) { 
                $icon_url = LatitudeConstants::NZ_ICON_URL;
                $icon_alt_text = "GEM Interest Fee";
            }
			ob_start();

			?><img src="<?php echo $icon_url; ?>" alt="<?php echo $icon_alt_text; ?>" /><?php

			return ob_get_clean();
             
        }  
        
 		/**
		 *
		 * Hooked onto the "woocommerce_order_button_text" filter.
		 *  
		 */
        public function filter_place_order_button_text($button) {    
            $current_payment_method     = WC()->session->get('chosen_payment_method'); // The chosen payment    
            if(  $current_payment_method == LatitudeConstants::WC_LATITUDE_GATEWAY_ID ) { 
                $button = 'Choose a plan';
            }  
            return $button;
        }         
        
		/**
		 * Display as a payment option on the checkout page.
		 *
		 */        
        public function payment_fields() {    
            // TODO: add field validations here   
            $this->log("payment_fields here");

			# Give other plugins a chance to manipulate or replace the HTML echoed by this funtion.
			ob_start();
			include "{$this->include_path}/Payment_Fields.html.php"; 
			$html = ob_get_clean(); 
			echo apply_filters( 'latitude_html_at_checkout', $html);        
        }

		/**
		 * Default process payment
		 *
		 */    
        public function process_payment( $order_id ) { 

            if(!$order_id) {
                // log error for null order_id
                return;
            } 

            $payload = $this->payment_request->build_request_parameters($order_id, $this->merchant_id, $this->test_mode);
            $this->log(__('payload: ' . wp_json_encode($payload)));

            // send the post via wp_remote_post
            $url = $this->payment_request->get_api_url($this->test_mode);    
            $this->log(__('sending to: ' . $url));       

            $response = wp_remote_post($url, array(
                'method'      => 'POST',
                'headers'     => array(
                    'Authorization' => 'Basic ' . base64_encode( $this->merchant_id . ':' . $this->merchant_secret ),
                    'Content-Type'  => 'application/json',
                ),
                'body'        => wp_json_encode($payload) 
            ));
            $this->log(json_encode($response));  

            global $woocommerce;
            $order = new WC_Order( $order_id );   

            $rsp_code = $response['response']['code'];
            if ($rsp_code != "200") {
                $error_string = $response['response']['message'];
                if (empty($error_string)) {
                    $error_string = "Bad request.";
                }
                $this->log(__("error (response code): ". $error_string));
                return array(
                    'result' => 'failure',
                    'redirect' => $order->get_checkout_payment_url(false), //TODO: Go back to cart
                );     
            }

            $rsp_body = json_decode($response['body'], true); 

            $result = $rsp_body['status'];
            if ($result != "completed") {
                $error_string = $rsp_body['error'];
                if (empty($error_string)) {
                    $error_string = "Request failure";
                }
                $this->log(__("error (response body): ". $error_string));
                return array(
                    'result' => 'failure',
                    'redirect' => $order->get_checkout_payment_url(false), //TODO: Go back to cart
                );     
            }

            $error_string = $this->is_valid_order_response($rsp_body, $order);
            if (!empty($error_string)) {
                $this->log(__("error (is_valid_order_response): ". $error_string));
                return array(
                    'result' => 'failure',
                    'redirect' => $order->get_checkout_payment_url(false), //TODO: Go back to cart
                );     
            }

           
            $redirectUrl = $rsp_body['redirectUrl'];
            $txnReference = $rsp_body['transactionReference'];     
            $this->log(__("redirectUrl: ". $redirectUrl));
            $this->log(__("transactionReference: ". $txnReference));

           // Reduce stock levels
            $order->reduce_order_stock();
                    
            // Remove cart
            WC()->cart->empty_cart();
            // Return thankyou redirect
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url( $order ),
            );        

        }    

         /**
		 *
		 * Validates contents of the purchase_request to match current order.
		 *  
		 */
        protected function is_valid_order_response($response, $order) {
            $error = "";
            if ($response['merchantId'] != $this->merchant_id) {
                $error = "Failed to confirm Merchant ID.";
                return $error;
            }
            if ($response['merchantReference'] != $order->get_id()) {
                $error = "Failed to confirm Order reference.";
                return $error;
            }
            if ($response['amount'] != $order->get_total()) {
                $error = "Failed to confirm transaction amount.";
                return $error;
            }     
            if ($response['currency'] != $order->get_currency()) {
                $error = "Failed to confirm transaction urrency";
                return $error;
            }               
            return $error;
        }

        public function receipt_page($order_id) { 
        }
  
        /**
		 * Logging method. 
		 */
		public static function log($message) {
			if (is_null(self::$log_enabled)) {
				# Get the settings key for the plugin
				$gateway = new WC_LatitudeCheckoutGateway;
				$settings_key = $gateway->get_option_key();
				$settings = get_option( $settings_key );

				if (array_key_exists('test_mode', $settings)) {
					self::$log_enabled = ($settings['test_mode'] == 'yes');
				} else {
					self::$log_enabled = false;
				}
			}
			if (self::$log_enabled) {
				if (is_null(self::$log)) {
					self::$log = wc_get_logger();
                } 
                if (is_array($message)) { 
                    $message = print_r($message, true);
                } elseif(is_object($message)) { 
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
                self::$log->debug($message, array('source' => 'latitude_checkout'));
			}
		}

    }
}