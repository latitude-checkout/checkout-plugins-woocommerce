<?php
/**
 * Latitude Checkout Service API Handler Class
 */

class Latitude_Checkout_Service
{
    /**
     * Latitude Checkout Service urls
     *
     */
    private const API_URL_TEST = 'https://api.dev.latitudefinancial.com/v1/applybuy-checkout-service';
    private const API_URL_PROD = 'https://api.test.latitudefinancial.com/v1/applybuy-checkout-service';

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
    public function send_purchase_request($payload)
    {
        // send the post via wp_remote_post
        $url = $this->get_purchase_api($this->gateway->get_test_mode());
        $this->gateway::log_debug(__('sending to: ' . $url));

        $response = wp_remote_post($url, [
            'method' => 'POST',
            'headers' => [
                'Authorization' => $this->build_auth_header(),
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($payload),
        ]);
        $this->gateway::log_debug(json_encode($response));

        $rsp_code = $response['response']['code'];
        if ($rsp_code != '200') {
            // get error from somewhere else

            $error_string = $response['response']['message'];
            if (empty($error_string)) {
                $error_string = 'Bad request.';
            }
            return [
                'result' => 'failure',
                'response' => $error_string,
            ];
        }

        $rsp_body = json_decode($response['body'], true);
        return [
            'result' => 'success',
            'response' => $rsp_body,
        ];
    }

    /**
     * Verifies the purchase request
     *
     */
    public function verify_purchase_request($payload)
    {
        $this->gateway::log_debug(
            __('verify request payload: ' . wp_json_encode($payload))
        );

        $url = $this->get_verify_purchase_api($this->gateway->get_test_mode());
        $this->gateway::log_debug(
            __('sending verify_purchase_request to: ' . $url)
        );

        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => $this->build_auth_header(),
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($payload),
        ]);

        $this->gateway::log_debug(json_encode($response));
        $rsp_code = $response['response']['code'];
        if ($rsp_code != '200') {
            return false;
        }

        try {
            $rsp_body = json_decode($response['body'], true);
            $verify_rsp = [
                'result' => 'success',
                'response' => $rsp_body,
            ];
        } catch (Exception $ex) {
            $verify_rsp = false;
        }
        return $verify_rsp;
    }

    /**
     * Builds basic authentication headers
     *
     */
    private function build_auth_header()
    {
        $merchant_id = $this->gateway->get_merchant_id();
        $secret_key = $this->gateway->get_secret_key();
        return 'Basic ' . base64_encode($merchant_id . ':' . $secret_key);
    }

    /**
     * Returns url for /purchase endpoint
     *
     */
    private function get_purchase_api($is_test)
    {
        $url = __(
            ($is_test ? self::API_URL_TEST : self::API_URL_PROD) . '/purchase'
        );
        return $url;
    }

    /**
     * Returns url for /purchase/verify endpoint
     *
     */
    private function get_verify_purchase_api($is_test)
    {
        $url = __(
            ($is_test ? self::API_URL_TEST : self::API_URL_PROD) .
                '/purchase/verify'
        );
        return $url;
    }
}
