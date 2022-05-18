<?php

/**
 * Latitude Checkout Service Capture Request API Handler Class
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Latitude_Checkout_Service_API_Capture extends Latitude_Checkout_Service_API
{
    /**
     * Protected variables.
     * @var	WC_Latitude_Checkout_Gateway $gateway
     * Reference to the WooCommerce Latitude Checkout Payment Gateway.
     */
    protected $gateway;
    protected $request_factory;

    const ENDPOINT = "/capture";
    const ERROR = "error";
    const MESSAGE = "message";
    const BODY = "body";
    const RESULT = "result";
    const RESPONSE = "response";
    const SUCCESS = "success";

    public function __construct()
    {
        $this->gateway = WC_Latitude_Checkout_Gateway::get_instance();
        $this->request_factory = new Latitude_Checkout_Capture_Data_Factory();
    }

    /**
     * Sends the capture request
     */
    public function request($order_id, $desc)
    {
        try {
            $capture_request = $this->request_factory->get_payload($order_id, $desc);

            if (isset($capture_request[self::ERROR]) && $capture_request[self::ERROR]) {
                throw new Exception($capture_request[self::MESSAGE]);
            }

            $capture_response = $this->post(self::ENDPOINT, $capture_request);

            if ($capture_response[self::RESULT] != self::SUCCESS) {
                return $this->handle_error($capture_response[self::ERROR]);
            }

            if ($capture_response[self::RESPONSE][self::RESULT] != Latitude_Checkout_Constants::RESULT_COMPLETED) {
                return $this->handle_error($capture_response[self::RESPONSE][self::ERROR]);
            }

            return $this->handle_success($capture_response[self::RESPONSE]);
        } catch (\Exception $ex) {
            return $this->handle_error($ex->getMessage());
        }
    }

    private function handle_success($body)
    {
        return [
            self::ERROR => false,
            self::BODY => $body
        ];
    }

    private function handle_error($message)
    {
        $this->gateway->log_info(__METHOD__. " ". $message);

        return [
            self::ERROR => true,
            self::MESSAGE => $message
        ];
    }
}
