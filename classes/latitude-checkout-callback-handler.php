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
 
}
Latitude_Checkout_API_Callbacks::get_instance();