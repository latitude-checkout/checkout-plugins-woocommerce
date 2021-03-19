<?php

class Latitude_Checkout_Service
{
    private const API_URL_TEST = 'https://api.dev.latitudefinancial.com/v1/applybuy-checkout-service';
    private const API_URL_PROD = 'https://api.test.latitudefinancial.com/v1/applybuy-checkout-service';

    protected $gateway;

    protected $logger = null;

    public function __construct()
    {
        $this->gateway = WC_LatitudeCheckoutGateway::get_instance();
    }

    private function log($message)
    {
        if ($this->logger === null) {
            $this->logger = wc_get_logger();
        }
        if ($this->gateway->get_test_mode()) {
            $this->logger->debug($message, ['source' => 'latitude_checkout']);
        }
    }

    public function send_purchase_request($payload)
    {
        // send the post via wp_remote_post
        $url = $this->get_purchase_api($this->gateway->get_test_mode());
        $this->log(__('sending to: ' . $url));

        $response = wp_remote_post($url, [
            'method' => 'POST',
            'headers' => [
                'Authorization' => $this->build_auth_header(),
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($payload),
        ]);
        $this->log(json_encode($response));

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

    public function verify_purchase_request($payload)
    {
        $this->log(__('verify request payload: ' . wp_json_encode($payload)));

        $url = $this->get_verify_purchase_api($this->gateway->get_test_mode());
        $this->log(__('sending verify_purchase_request to: ' . $url));

        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => $this->build_auth_header(),
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($payload),
        ]);

        $this->log(json_encode($response));
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

    private function build_auth_header()
    {
        $merchant_id = $this->gateway->get_merchant_id();
        $secret_key = $this->gateway->get_secret_key();
        return 'Basic ' . base64_encode($merchant_id . ':' . $secret_key);
    }

    private function build_user_agent_header()
    {
        global $wp_version;
        $plugin_version = WC_LatitudeCheckoutGateway::$version;
        $php_version = PHP_VERSION;
        $woocommerce_version = WC()->version;
        $merchant_id = $this->gateway->get_merchant_id();
        return "LatitudeCheckout Gateway for WooCommerce/{$plugin_version} (PHP/{$php_version}; WordPress/{$wp_version}; WooCommerce/{$woocommerce_version}; Merchant/{$merchant_id})";
    }

    private function get_purchase_api($is_test)
    {
        $url = __(
            ($is_test ? self::API_URL_TEST : self::API_URL_PROD) . '/purchase'
        );
        return $url;
    }

    private function get_verify_purchase_api($is_test)
    {
        $url = __(
            ($is_test ? self::API_URL_TEST : self::API_URL_PROD) .
                '/purchase/verify'
        );
        return $url;
    }
}
