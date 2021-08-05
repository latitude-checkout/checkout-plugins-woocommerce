<?php
/**
 * Latitude Checkout Service API Handler Class
 */
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use Latitude_Checkout_Environment_Settings;

class Latitude_Checkout_Service_API
{
   
   /**
     * Protected variables.
     *
     * @var		WC_Latitude_Checkout_Gateway	$gateway		A reference to the WooCommerce Latitude Checkout Payment Gateway.
     */
    protected $gateway;

    const REQUEST_TIMEOUT = 30;

    public function __construct()
    {
        $this->gateway = WC_Latitude_Checkout_Gateway::get_instance();
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
        $is_test_mode = $this->gateway->is_test_mode();
        return Latitude_Checkout_Environment_Settings::get_service_url($is_test_mode);
    }

    protected function get_request_headers()
    {
        return array(
            'Authorization' => $this->build_auth_header(),
            'Content-Type' => 'application/json',
            'Referer' => get_site_url()
        );
    }

    protected function get_post_request_args($payload)
    {
        return array(
            'timeout'   => self::REQUEST_TIMEOUT,
            'headers'   => $this->get_request_headers(),
            'body'      => json_encode($payload),
        );
    }

    protected function post($endpoint, $payload)
    {
        $payload_with_headers = $this->get_post_request_args($payload);
        $response = wp_remote_post($this->get_api_url() . $endpoint, $payload_with_headers);

        $response =  $this->process_response($response);

        if ($this->gateway->is_debug_mode()) {
            $this->gateway::log_info($url . " (REQUEST): ". json_encode($payload));
            $this->gateway::log_info($url . " (RESPONSE STATUS CODE): ". $response["response_code"]);
            $this->gateway::log_info($url . " (RESPONSE BODY): ". json_encode($response));
        }

        return $response;
    }

    protected function process_response($response)
    {
        $this->gateway::log_debug(__('process_response: ' . json_encode($response)));
        if (is_wp_error($response)) {
            $error_string = implode($response->get_error_messages(), ' ');
            return array(
                'result' => 'failure',
                'response_code' => "error",
                'error' => $error_string,
                'override_notice' => ''
            );
        }

        //TODO: refactor error status code handling

        $rsp_code = wp_remote_retrieve_response_code($response);
        if (($rsp_code >= 200) && ($rsp_code <= 299)) {
            $rsp_body = json_decode($response['body'], true);
            return array(
                'result' => 'success',
                'response_code' => $rsp_code,
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
            'response_code' => $rsp_code,
            'error' => $result_string,
            'override_notice' => $notice_message
        );
    }
}
