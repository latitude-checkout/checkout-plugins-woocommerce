<?php
/**
 * Latitude Checkout API Callback Class
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
 

/**
 * Latitude_Checkout_API_Callbacks class.
 *
 * Class that handles API callbacks.
 */
 
class Latitude_Checkout_API_Callbacks
{ 
  	/**
	 * The reference the *Singleton* instance of this class.
	 *
	 * @var $instance
	 */
	private static $instance;
 
	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return Latitude_Checkout_API_Callbacks The *Singleton* instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

    /**
	 * Latitude_Checkout_API_Callbacks constructor.
	 */
	public function __construct() { 
        add_action('woocommerce_api_lc-purchase-complete', [$this,'on_complete_purchase',]); 
        add_action('woocommerce_before_cart', [ $this, 'on_cancel_purchase']);
	}
 
    /**
     *
     * Hooked onto the "woocommerce_api_lc-purchase-complete" action.
     *
     */ 
    public function on_complete_purchase()
    {
        $lc_gateway = WC_LatitudeCheckoutGateway::get_instance();
        $lc_gateway::log_debug('on_complete_purchase');
        $merchantReference = filter_input( INPUT_GET, 'merchantReference', FILTER_SANITIZE_STRING );    
        $transactionReference = filter_input( INPUT_GET, 'transactionReference', FILTER_SANITIZE_STRING );  
        $gatewayReference = filter_input( INPUT_GET, 'gatewayReference', FILTER_SANITIZE_STRING );  

        $response = $lc_gateway->api_service->verify_purchase_request($merchantReference, $transactionReference, $gatewayReference);
        $lc_gateway::log_info( __( "purchase_request result: "  . json_encode($response)));
        if (is_array($response) && ($response['redirectURL'] !== '')) { 
            wp_redirect($response['redirectURL']);   
        } else {
            wp_redirect( wc_get_checkout_url()); 
        } 
        exit; 
    }

     /**
      *
      * Hooked onto the "woocommerce_before_cart" action.
      *
      */ 
      public function on_cancel_purchase()
      { 
          $lc_gateway = WC_LatitudeCheckoutGateway::get_instance();
          $current_payment_method = WC()->session->get( 'chosen_payment_method'  );   
          if ( $current_payment_method !=  $lc_gateway->get_payment_gateway_id() ) {
              return;
          }   
 
          if (array_key_exists('cancel_order', $_GET) && array_key_exists('order_id', $_GET) && 
                  $_GET['cancel_order'] === 'true') {
              $order_id = (int)$_GET['order_id']; 
              $lc_gateway::log_info(__("Order cancelled by customer:{$order_id}"));
              $order = wc_get_order($order_id); 
              $order->update_status('cancelled'); 
          }
 
      }    
 
}
Latitude_Checkout_API_Callbacks::get_instance();