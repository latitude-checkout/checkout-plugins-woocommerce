<?php
/**
 * Latitude Checkout Service API Handler Class
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Environment_Settings as EnvSettings; 

class Latitude_Service_API 
{
   
   /**
     * Protected variables.
     *
     * @var		WC_LatitudeCheckout_Gateway	$gateway		A reference to the WooCommerce Latitude Checkout Payment Gateway.
     */
    protected $gateway;

    public function __construct()
    {
        $this->gateway = WC_LatitudeCheckout_Gateway::get_instance();
    }   
 
    /**
     * Builds basic authentication headers
     *
     */
    protected function build_auth_header()
    {
        $merchant_id = $this->gateway->get_merchant_id();
        $secret_key = $this->gateway->get_secret_key();
        return 'Basic ' . base64_encode($merchant_id . ':' . $secret_key);
    }

    protected function get_api_url()
    {
        $env = $this->gateway->get_api_settings();
        return EnvSettings::api_settings[$env]["checkout_service_url" ];
    }

    protected function get_request_headers() 
    { 
        return array(
            'Authorization' => $this->build_auth_header(),
            'Content-Type' => 'application/json',
            'Referer' => get_site_url() // For local test: 'https://woo.local.store/'
        ); 
    }

    protected function get_post_request_args($payload) 
    {
        return array(
            'headers' => $this->get_request_headers(), 
            'body' => json_encode($payload)
        );
    }

    protected function process_response($response) 
    {  
        $this->gateway::log_debug( __('process_response: ' . json_encode($response)) ); 
        if (is_wp_error($response)) {
            $error_string = implode($response->get_error_messages(), ' '); 
            return array(
                'result' => 'failure',
                'error' => $error_string,
                'override_notice' => ''
            );
        }

        $rsp_code = wp_remote_retrieve_response_code( $response );
        if (($rsp_code >= 200) && ($rsp_code <= 299)) {
            $rsp_body = json_decode($response['body'], true); 
            return array(
                'result' => 'success', 
                'response' => $rsp_body 
            ) ;   
        }
  
        $this->gateway::log_error(json_encode($response));  
        $result_string = $response['response']['message'];
        if (empty($result_string)) {
            $result_string = 'Bad Request';
        }  

        $notice_message = '';
        if (($rsp_code == 401) || ($rsp_code == 403)) {
            $notice_message = __('Merchant credentials doesnt look correct. Please notify this error to merchant.', 'woo_latitudecheckout');
        }
 
        return array(
            'result' => 'failure',
            'error' => $result_string,
            'override_notice' => $notice_message 
        ); 
    }
}
