<?php

/**
 * Latitude Checkout Service Void Request API Handler Class
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Latitude_Checkout_Service_API_Void extends Latitude_Checkout_Service_API
{
    /**
     * Protected variables.
     * @var	WC_Latitude_Checkout_Gateway $gateway
     * Reference to the WooCommerce Latitude Checkout Payment Gateway.
     */
    protected $gateway;
    protected $request_factory;

    const ENDPOINT = "/void";
    const ERROR = "error";
    const MESSAGE = "message";
    const BODY = "body";
    const RESULT = "result";
    const RESPONSE = "response";
    const SUCCESS = "success";

    public function __construct()
    {
        $this->gateway = WC_Latitude_Checkout_Gateway::get_instance();
        $this->request_factory = new Latitude_Checkout_Void_Data_Factory();
    }

    /**
     * Sends the void request
     */
    public function request($order_id, $desc)
    {
        try {
            $void_request = $this->request_factory->get_payload($order_id, $desc);

            if (isset($void_request[self::ERROR]) && $void_request[self::ERROR]) {
                throw new Exception($void_request[self::MESSAGE]);
            }

            $void_response = $this->post(self::ENDPOINT, $void_request);

            if ($void_response[self::RESULT] != self::SUCCESS) {
                return $this->handle_error($void_response[self::ERROR]);
            }

            if ($void_response[self::RESPONSE][self::RESULT] != Latitude_Checkout_Constants::RESULT_COMPLETED) {
                return $this->handle_error($void_response[self::RESPONSE][self::ERROR]);
            }

            return $this->handle_success($void_response[self::RESPONSE]);
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
