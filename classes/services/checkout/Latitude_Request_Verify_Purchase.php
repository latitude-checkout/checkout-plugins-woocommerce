<?php
/**
 * Latitude Checkout Service API Handler Class
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Latitude_Request_Verify_Purchase extends Latitude_Service_API
{
   
   /**
     * Protected variables.
     *
     * @var		WC_LatitudeCheckoutGateway	$gateway		A reference to the WooCommerce Latitude Checkout Payment Gateway.
     */
    protected $gateway;

    public function __construct()
    {
        $this->gateway = WC_LatitudeCheckoutGateway::get_instance();
    }

    /**
     * Sends the verify purchase request
     *
     */
    public function request($merchantReference, $transactionReference, $gatewayReference) {

        if ($this->is_valid_params($merchantReference, $transactionReference, $gatewayReference) === false) {
            return $this->return_request_error(null, 'Purchase request callback parameters were not valid', 'Failed to verify this payment. Please contact merchant.');
        }

        $this->gateway::log_debug(
            "merchantReference: {$merchantReference}, transactionReference: {$transactionReference}, gatewayReference: {$gatewayReference}"
        );
        $url = $this->get_verify_purchase_url();   
        $request_args = $this->get_request_args($merchantReference, $transactionReference, $gatewayReference); 
        $response = wp_remote_post($url, $request_args);  
        $result =  $this->process_response($response); 
        $this->gateway::log_debug( __('verify_purchase_request response: ' . json_encode($result)) );
        return $this->parse_results($merchantReference, $transactionReference, $gatewayReference, $result);
    } 

    private function is_valid_params($merchantReference, $transactionReference, $gatewayReference)
    {
        if (is_null($merchantReference) || ($merchantReference == false) || is_null($transactionReference) || is_null($gatewayReference)) {
            return false;
        } 
        return true;
    }

    /**
     * build the verify purchase request header and body
     *
     */
    private function get_request_args($merchantReference, $transactionReference, $gatewayReference)
    { 
        $payload = array( 
            'gatewayReference' => $gatewayReference,
            'transactionReference' => $transactionReference,
            'merchantReference' => $merchantReference,
        ); 
        $this->gateway::log_debug( __('verify_purchase_request payload: ' . json_encode($payload)) );
        return $this->get_post_request_args($payload);
    } 

     
    /**
     * Returns url for /purchase/verify endpoint
     *
     */
    private function get_verify_purchase_url()
    {
        $url = __( $this->get_api_url() . '/purchase/verify' ); 
        $this->gateway::log_debug( __('sending verify_purchase_request to: ' . $url) );
        return $url;
    }

    /**
     * parse result from verify purchase request
     *
     */
    private function parse_results($order_id, $transactionReference, $gatewayReference, $response) 
    {    
        $notice_message = 'Payment declined for this order. Please try again or select other payment method.';
        $error_string = $this->get_payment_status_string('Payment declined', $transactionReference, $gatewayReference);  
        if (is_null($response) || $response == false || !is_array($response)) { 
            return $this->return_request_error($order, $error_string, $notice_message);
        }

        $order = wc_get_order($order_id);  
        $rsp_body = $response['response'];
        $this->gateway::log_debug($rsp_body);
        $result = $rsp_body['result'];

        if ( $result !== 'completed') {  
            $message = $rsp_body['message'];
            if (!empty($message)) { 
                $message = _( "Verify Purchase Request failed for WooCommerce Order #{$order_id}. API error message returned:{$message}.");  
            } else {
                $message = __( "Verify Purchase Request failed for WooCommerce Order #{$order_id}."); 
            }    
            $this->gateway::log_error($message); 
            $order->add_order_note( __( $message, 'woo_latitudecheckout' )  ); 
            return $this->return_request_error($order, $error_string, $notice_message); 
        }

        // for completed
        $transactionType = $rsp_body['transactionType'];
        $promotionReference = $rsp_body['promotionReference'];
        if ($transactionType == 'sale') {
            $status_string = $this->get_payment_status_string('Payment approved', $transactionReference, $gatewayReference);
            $this->gateway::log_info( "WooCommerce Order #{$order_id} transaction is \"completed\".  {$status_string}" );
            $order->add_order_note($status_string); 
            $order->payment_complete();
        } elseif ($transactionType == 'authorisation') {
            $status_string = $this->get_payment_status_string('Payment under authorisation', $transactionReference, $gatewayReference);
            $this->gateway::log_info( "WooCommerce Order #{$order_id} has {$status_string}" );
            $order->add_order_note($status_string);  
            $order->update_status( 'on-hold', __( $status_string, 'woo_latitudecheckout' ) );
        } else {
            $payment_status = __("Payment transaction type: {$transactionType}");
            $status_string = $this->get_payment_status_string($payment_status, $transactionReference, $gatewayReference);
            $this->gateway::log_info( "WooCommerce Order #{$order_id} has {$status_string}" );
            $order->add_order_note($status_string);            
        } 
        $order->update_meta_data( 'gatewayReference', $gatewayReference  );
        $order->update_meta_data( 'transactionReference', $transactionReference );
        $order->update_meta_data( 'promotionReference', $promotionReference );
        $order->update_meta_data( 'transactionType', $transactionType );
        $order->save();
        wc_empty_cart();
        return array(
            'valid' => true, 
            'redirectURL' => $order->get_checkout_order_received_url() 
        );
    } 

    private function get_payment_status_string($status, $transactionReference, $gatewayReference ) {
        return sprintf(  __( '%s. Transaction reference: %s, Gateway reference: %s.',
                        'woo_latitudecheckout'
                    ), $status, $transactionReference, $gatewayReference
                );
    }

    private function return_request_error($order, $error_string, $notice_message) {
        $this->gateway::log_error($error_string);  
        if (!is_null($order)) {
            $order->add_order_note($error_string);  
            $order->update_status('failed');
        } 
        wc_add_notice(__( $notice_message, 'woo_latitudecheckout'), 'error');
        return array(
            'valid' => false, 
            'redirectURL' => wc_get_checkout_url() 
        );
    }

   
}
