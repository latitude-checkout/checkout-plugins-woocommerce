<?php
/**
 * Latitude Checkout Service API Base Class
 */
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
 
/**
 * Latitude_Checkout_Service_API class
 *
 * Class that handles API requests.
 */

class Latitude_Checkout_API
{
    public function purchase_request($order_id)
    {
        $request = new Latitude_Checkout_Service_API_Purchase();
        $response = $request->request($order_id);
        return $response;
    }

    public function verify_purchase_request($merchantReference, $transactionReference, $gatewayReference)
    {
        $request = new Latitude_Checkout_Service_API_Verify_Purchase();
        $response = $request->request($merchantReference, $transactionReference, $gatewayReference);
        return $response;
    }
}
