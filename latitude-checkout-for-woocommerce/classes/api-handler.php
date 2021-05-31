<?php
/**
 * Latitude Checkout Service API Base Class
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Latitude_Checkout_Environment_Settings as Environment; 

/**
 * Latitude_Chekout_API class.
 *
 * Class that handles API requests.
 */

class Latitude_Chekout_API 
{ 
    public function purchase_request($order_id) {
        $request = new Latitude_Request_Purchase(); 
        $response = $request->request($order_id);
        return $response;
    }

    public function verify_purchase_request($merchantReference, $transactionReference, $gatewayReference) {
        $request = new Latitude_Request_Verify_Purchase();
        $response = $request->request($merchantReference, $transactionReference, $gatewayReference);
        return $response;
    }
}