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
        protected $merchant_id, $merchant_secret, $test_mode, $purchase_request, $checkout_service = null;          
        
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



        }  

        /**
		 * Instantiate the class if no instance exists. Return the instance.
		 * 
		 */
		public static function get_instance()
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
				$this->merchant_secret = $this->settings['merchant_secret'];
            } 
			if (array_key_exists('testmode', $this->settings)) {
                $this->test_mode = ('yes' === $this->settings['testmode']);
                self::$log_enabled = $this->test_mode;
			} 
        }
        
        /**
		 * Get the Merchant ID from our user settings.  
		 */
		public function get_merchant_id() { 
			return $this->settings['merchant_id'];
		}

		/**
		 * Get the Merchant Secret Key from our user settings.  
		 */
		public function get_secret_key() {
			return $this->settings['merchant_secret'];
        }
        
        /**
		 * Get the Test Mode Enabled from our user settings.  
		 */
		public function get_test_mode() {
            $this->test_mode = ('yes' === $this->settings['testmode']); 
			return ($this->test_mode );
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
            
            // $this->by_pass_purchase_endpoint($order_id);
            
            $purchase_request = new Latitude_Purchase_Request;  
            $this->log('process_payment'); 
            $payload = $purchase_request->build_parameters($order_id);
            $this->log(__('payload: ' . wp_json_encode($payload)));

          
            $checkout_service = new Latitude_Checkout_Service;
            $response = $checkout_service->send_purchase_request($payload);

            // global $woocommerce;
            // $order = new WC_Order( $order_id );    
            $order = wc_get_order( $order_id ); 

            if ($response == false) {
                return $this->redirect_to_cart_on_error($order, "Invalid purchase request.");  
            }

            if (is_array($response)  && $response['result'] == 'failure') {
                $error_string = __("Error on purchase request: " . $response['response'] ); 
                return $this->redirect_to_cart_on_error($order, $error_string);  
            }
               
            $rsp_body = $response['response'];
            $result = $rsp_body['result'];
            if ($result == 'failed') {
                $error_string = $rsp_body['error'];
                if (empty($error_string)) {
                    $error_string = "Request failure";
                } 
                $this->log(__("error (response body): ". $error_string)); 
                return $this->redirect_to_cart_on_error($order, $error_string);  
            }

            if ($result == 'pending') { 
                $error_string = $this->is_valid_order_response($rsp_body, $order);
                if (!empty($error_string)) { 
                    $this->log(__("Error:  (is_valid_order_response): ". $error_string));
                    $error_string = __( "Error: Payment request returned invalid order details. Order Reference: {$order_id}. " . $error_string );
                    return $this->redirect_to_cart_on_error($order, $error_string); 
                } 
                
                $redirect_url = $rsp_body['redirectUrl'];
                $this->log(__("redirectUrl: ". $redirect_url));
                if (empty($redirect_url)) { 
                    return $this->redirect_to_cart_on_error($order, "Latitude Interest Free Gateway is not reachable."); 
                }

                $transaction_reference = $rsp_body['transactionReference'];     
                $this->log(__("transactionReference: ". $transaction_reference)); 
                if (empty($transaction_reference)) {
                    return $this->redirect_to_cart_on_error($order, "Latitude Interest Free Gateway cannot validate transaction"); 
                }  
                update_post_meta( $order_id , '_transactionReference', $transaction_reference);
     
                return array(
                    'result' => 'success',
                    'redirect' => $redirect_url,
                );       
            }

            $this->log("Error: Payment request cannot proceed for this order. Order Reference: {$order_id}");
            return array(
                'result' => 'failure',
                'redirect' => $order->get_checkout_payment_url(false), //TODO: Go back to cart
            );     

        }    

        // FOR TESTING verify ENDPOINT only
        private function by_pass_purchase_endpoint($order_id) {
            $payload = array(   
                "transactionReference" => "d344bd7d-6661-4366-8b42-0d4d62593bb8",
                "merchantReference" => "424"
            ); 
            $checkout_service = new Latitude_Checkout_Service;
            $response = $checkout_service->verify_purchase_request($payload); 

            $order = wc_get_order( $order_id ); 
            return array(
                'result' => 'failure',
                'redirect' => $order->get_checkout_payment_url(false), //TODO: Go back to cart
            );    
        }

        /**
		 *
		 * Displays the error message on the cart.
		 *  
		 */
        private function redirect_to_cart_on_error($order, $error_string) { 
            wc_add_notice( __( $error_string , 'woo_latitudecheckout' ), 'error' );
            return array(
                'result' => 'failure',
                'redirect' => $order->get_checkout_payment_url(false), //TODO: Go back to cart
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

        public function filter_order_pay_title($old_title, $payment_declined ) {
            //order-pay
            if ($payment_declined) {
                return "Payment failed";
            }
            return old_title;
           
        }

        public function receipt_page($order_id) {  
            $this->log('receipt_page');  

            $order = wc_get_order( $order_id );   
            $transaction_reference = $order->get_meta('_transactionReference');
            $this->log(__('(on receipt_page) transaction_reference: ' . $transaction_reference)); 

            $is_pending = false;
            if (method_exists($order, 'has_status')) {  
                $is_pending = $order->has_status( 'pending' ); 
            } else {
                $this->log("order status: {$order->status}"); 
                if ($order->status == 'pending') {
                    $is_pending = true;
                }
            }

            $this->log(__('(on receipt_page) is_pending: ' . $is_pending)); 
            if ($is_pending) {
                wc_print_notice("Payment declined for this order. ");
                apply_filters( 'woocommerce_endpoint_order-pay_title', 'Pay for order', true );
            }

        }

        public function on_payment_callback($order_id) {   
            $this->log('on_payment_callback');  

            $order = wc_get_order( $order_id );   
            //get current order status

            $transaction_reference = $order->get_meta('_transactionReference');
            $this->log(__('transaction_reference: ' . $transaction_reference)); 
            if ( empty($transaction_reference)) {
                // exit and redirect to card?
               return $order_id;
            }
        
            $payload = array(  
                "transactionReference" => $transaction_reference,
                "merchantReference" => strval($order_id)
            ); 

            $checkout_service = new Latitude_Checkout_Service;
            $response = $checkout_service->verify_purchase_request($payload);  

            if ($response === false) { 
                $this->log("Payment declined for WooCommerce Order #{$order_id}. Cannot verify this transaction.");
                $order->add_order_note( sprintf(__( 'Latitude Checkout Payment declined. Transaction reference: %s. Transaction cannot be verified.', 'woo_latitudecheckout' ), $transaction_reference) ); 

                // TODO: choose from either way to display error
                /** option 1 **/
                // wc_add_notice( __( 'Payment failed due to /verify api error', 'woo_latitudecheckout' ), 'error' );
                // if (wp_redirect( wc_get_checkout_url() )) {
                //     exit;
                // }
                
                wp_redirect(  $order->get_checkout_payment_url(true));
                exit; 
            } elseif (is_array($response)) {
                $rsp_body = $response['response']; 
                $this->log("verify_purchase_request() returned data");
                $this->log($rsp_body);

                $is_pending = false;
                if (method_exists($order, 'has_status')) {  
                    $is_pending = $order->has_status( 'pending' ); 
                } else {
                    $this->log("order status: {$order->status}"); 
                    if ($order->status == 'pending') {
                        $is_pending = true;
                    }
                }

                $result = $rsp_body['result'];
                $payment_completed = false;
                if ($result == "completed") {
                    if ($is_pending) {
                        //TODO ??? -> display error and redirect to cart
                        $gateway_reference =  $result = $rsp_body['gatewayReference'];
                        $promotion_reference =  $result = $rsp_body['promotionReference'];
                        $merchant_reference =  $result = $rsp_body['merchantReference'];
                        update_post_meta($order_id,'_gatewayReference' ,$gateway_reference);
                        update_post_meta($order_id,'_promotionReference' ,$promotion_reference);
                        update_post_meta($order_id,'_merchantReference' , $merchant_reference);

                        $this->log("Updating status of WooCommerce Order #{$order_id} to \"completed\".");
                        $order->payment_complete($transaction_reference);
                        $payment_completed = true;
                        $order->add_order_note( sprintf(__( 'Payment approved. Transaction reference: %s', 'woo_latitudecheckout' ), $transaction_reference) ); 
                        wc_empty_cart();   
                    }   
                } 
                
                if (!$payment_completed) {
                    //TODO ??? -> payment failed -> display error and redirect to cart
                    $this->log("Payment declined for WooCommerce Order #{$order_id}");
                    $order->add_order_note( sprintf(__( 'Payment declined. Transaction reference: %s', 'woo_latitudecheckout' ), $transaction_reference) );   
                    wp_redirect(  $order->get_checkout_payment_url(true));
                   exit;  
                }

            } 
            return $order_id;
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