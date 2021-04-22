<?php
/**
 * Latitude Checkout Service API Handler Class
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Latitude_Request_Purchase extends Latitude_Service_API
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
     * Sends the purchase request payload
     *
     */
    public function request($order_id) {

        $url = $this->get_purchase_url();  
        $request_args = $this->get_request_args($order_id);  
        $response = wp_remote_post($url, $request_args);  
        $result =  $this->process_response($response); 
        $this->gateway::log_debug( __('purchase_request response: ' . json_encode($result)) );
        return $this->parse_results($order_id, $result);
    }
 

    /**
     * build the purchase request header and body
     *
     */
    private function get_request_args($order_id)
    {
        $request_order = new PurchaseRequest();
        $payload = $request_order->get_payload($order_id);  
        $this->gateway::log_debug( __('request payload: ' . json_encode($payload)) );
        return $this->get_post_request_args($payload);
    } 

    /**
     * Returns url for /purchase endpoint
     *
     */
    private function get_purchase_url()
    {
        $url = __( $this->get_api_url() . '/purchase' );
        $this->gateway::log_debug( __('sending purchase_request to: ' . $url) );
        return $url;
    }

    private function parse_results($order_id, $response) {

        $order = wc_get_order($order_id);  
        
        $notice_message = 'Purchase Request was not valid. Please contact Merchant.';
        if (is_null($response) || $response == false) { 
            return $this->return_purchase_request_error($order, 'Failed to validate Purchase Request API response.', $notice_message);
        }

        if (is_array($response) && $response['result'] == 'failure') {  
            $error_string = 'Purchase request API failed.';
            if (!empty($response['override_notice'])) {
                $notice_message = $response['override_notice'];
            }
            if ($response['error'] !== '') {
                $error_string = __( 'Purchase request API returned with error: ' . $response['error'] . '.' ); 
            } 
            return $this->return_purchase_request_error($order, $error_string, $notice_message); 
        }
        $rsp_body = $response['response'];
        return $this->parse_purchase_request($order, $rsp_body);
    }


    private function parse_purchase_request($order, $rsp_body)
    {   
        $this->gateway::log_debug( __('request response_body: ' . json_encode($rsp_body)) );
        $result = $rsp_body['result']; 
        $rsp_redirecturl = $rsp_body['redirectUrl']; 
        $rsp_error = $rsp_body['error']; 
        if ($result == 'pending') { 
            $result_data = $this->is_result_valid( $rsp_body, $order ) ;
            if ($result_data['valid'] === false) { 
                $notice_message = __( "{$result_data['error']} Please try again later or pay with other payment method. " );
                $error_string = __( " Invalid order response. Error: {$result_data['error']}" );
                return $this->return_purchase_request_error($order, $error_string, $notice_message);  
            } 
            if (empty($rsp_redirecturl)) {  
                $notice_message = 'Latitude Interest Free Service is not reachable. Please try again later or pay with other payment method. ';
                return $this->return_purchase_request_error($order, 'Latitude Interest Free Gateway is not reachable.', $notice_message);  
            }  
            return $this->return_purchase_response('success', $rsp_redirecturl ); 

        } elseif ($result == 'failed') { 

            if(!empty($rsp_redirecturl) && !empty($rsp_error)) {
                $error_string = __( "Purchase Request returned with error : {$rsp_error}.");
                $order->add_order_note(__( $error_string, 'woo_latitudecheckout' ) ); 
                $order->update_status('failed'); 
                return $this->return_purchase_response('success', $rsp_redirecturl ); 
            }

            $error_string = __( "Purchase Request failed. ", 'woo_latitudecheckout');  
        } else {
            // Purchase request returned unexpected result (neither pending nor failed) 
            $error_string =  __( 'Unexpected result received from purchase request: ' . json_encode($result) . '.', 'woo_latitudecheckout'); 
        }  
        return $this->return_purchase_request_error($order, $error_string, $notice_message); 
    }

    private function return_purchase_request_error($order, $error_string, $notice_message) {
        $error_string = __( $error_string . ' Please contact Latitude if problem persists. ', 'woo_latitudecheckout');
        $this->gateway::log_error($error_string);  
        
        $redirect_url = wc_get_checkout_url();
        if (!is_null($order)) { 
            $order->add_order_note($error_string);   
            $order->update_status( 'failed'); 
            $redirect_url = $order->get_checkout_payment_url(false);
        }
  
        wc_add_notice(__( $notice_message, 'woo_latitudecheckout'), 'error');
        return $this->return_purchase_response('failure', $redirect_url ); 
    }


    private function return_purchase_response($result, $redirect_url) {
        return array (
            'result' => $result,
            'redirect' =>  $redirect_url 
        );
    }

    /**
     *
     * Validates contents of the purchase_request to match current order.
     *
     */
    private function is_result_valid($response, $order)
    { 
        if ($response['merchantId'] != $this->gateway->get_merchant_id()) {
            return  array(
                'valid' => false,
                'error' => 'Failed to validate this Merchant.'
            ); 
        }
        if ($response['merchantReference'] != $order->get_id()) { 
            return  array(
                'valid' => false,
                'error' => 'Failed to confirm this order.'
            ); 
        }
        if ($response['amount'] != $order->get_total()) { 
            return  array(
                'valid' => false,
                'error' => 'Transaction amount cannot be validated.'
            ); 
        }
        if ($response['currency'] != $order->get_currency()) { 
            return  array(
                'valid' => false,
                'error' => 'Currency mismatch for this transaction.'
            );  
        }
        return array ('valid' => true);
    }

    
}
