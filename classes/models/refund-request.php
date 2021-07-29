<?php

/**
 * Latitude Checkout Refund Request Data Factory Class
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Latitude_Checkout_Refund_Data_Factory
{
    /**
     * Protected variables.
     * @var	WC_Latitude_Checkout_Gateway    $gateway
     * A reference to the WooCommerce Latitude Checkout Payment Gateway.
     */
    protected $gateway;

    const TRANSACTION_TYPE_REFUND = "refund";

    const ERROR = 'error';
    const MESSAGE = 'message';
    const BODY = 'body';

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->gateway = WC_Latitude_Checkout_Gateway::get_instance();
    }

    /**
     * Builds refund request payload
     */
    public function get_payload($order_id, $refund_amount, $refund_desc)
    {
        $order = $this->gateway->get_valid_order($order_id);
        $gatewayReference = "";
        
        if (is_null($order)) {
            return $this->handle_error("could not get order for id ". $order_id);
        }

        if(!$order->meta_exists(Latitude_Checkout_Constants::GATEWAY_REFERENCE)) {
            return $this->handle_error("could not get gateway reference for order ". $order_id);
        }

        $amount = $this->to_price($refund_amount);
        if($amount < 1) {
            return $this->handle_error("invalid refund amount ". $amount);
        }

        return [
            'merchantId' => $this->gateway->get_merchant_id(),
            'isTest' => $this->gateway->is_test_mode(),
            "gatewayReference" => (string)$order->get_meta(Latitude_Checkout_Constants::GATEWAY_REFERENCE),
            "merchantReference" => (string)$order_id,
            'amount' => $amount,
            'currency' => $order->get_currency(),
            "type" => self::TRANSACTION_TYPE_REFUND,
            "description" => $refund_desc,
            'platformType' => Latitude_Checkout_Constants::PLATFORM_NAME,
            'platformVersion' => WC()->version,
            'pluginVersion' => $this->gateway->get_plugin_version(),
        ];
    }

    private function to_price($val)
    {
        if (empty($val)) {
            return 0;
        }

        return round((float)$val, 2);
    }

    private function handle_error($message)
    {
        return [
            self::ERROR => true,
            self::MESSAGE => $message
        ];
    }
}
