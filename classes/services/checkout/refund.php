<?php

/**
 * Latitude Checkout Service Refund Request API Handler Class
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Latitude_Checkout_Service_API_Refund extends Latitude_Checkout_Service_API
{
    /**
     * Protected variables.
     * @var	WC_Latitude_Checkout_Gateway $gateway
     * Reference to the WooCommerce Latitude Checkout Payment Gateway.
     */
    protected $gateway;
    protected $request_factory;

    const ENDPOINT = "/refund";
    const ERROR = "error";
    const MESSAGE = "message";
    const BODY = "body";
    const RESULT = "result";
    const RESPONSE = "response";
    const SUCCESS = "success";

    public function __construct()
    {
        $this->gateway = WC_Latitude_Checkout_Gateway::get_instance();
        $this->request_factory = new Latitude_Checkout_Refund_Data_Factory();
    }

    /**
     * Sends the refund request
     */
    public function request($order_id, $refund_amount, $refund_desc)
    {
        try {
            $refund_request = $this->request_factory->get_payload($order_id, $refund_amount, $refund_desc);

            if (isset($refund_request[self::ERROR]) && $refund_request[self::ERROR]) {
                throw new Exception($refund_request[self::MESSAGE]);
            }

            $refund_response = $this->post(self::ENDPOINT, $refund_request);

            if ($refund_response[self::RESULT] != self::SUCCESS) {
                return $this->handle_error($refund_response[self::ERROR]);
            }

            if ($refund_response[self::RESPONSE][self::RESULT] != Latitude_Checkout_Constants::RESULT_COMPLETED) {
                return $this->handle_error($refund_response[self::RESPONSE][self::ERROR]);
            }

            return $this->handle_success($refund_response[self::RESPONSE]);
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
