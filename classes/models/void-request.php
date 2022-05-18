<?php

/**
 * Latitude Checkout Void Request Data Factory Class
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Latitude_Checkout_Void_Data_Factory
{
    /**
     * Protected variables.
     * @var	WC_Latitude_Checkout_Gateway    $gateway
     * A reference to the WooCommerce Latitude Checkout Payment Gateway.
     */
    protected $gateway;

    const TRANSACTION_TYPE_VOID = "void";

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
     * Builds void request payload
     */
    public function get_payload($order_id, $desc)
    {
        $order = $this->gateway->get_valid_order($order_id);
        $gatewayReference = "";
        
        if (is_null($order)) {
            return $this->handle_error("Could not get order for id {$order_id}");
        }

        if(!$order->meta_exists(Latitude_Checkout_Constants::GATEWAY_REFERENCE)) {
            return $this->handle_error("Could not get gateway reference for order {$order_id}");
        }

        if($order->get_meta(Latitude_Checkout_Constants::TRANSACTION_TYPE) != Latitude_Checkout_Constants::TRANSACTION_TYPE_AUTH) {
            return $this->handle_error("Could not void order {$order_id} without autorization");
        }

        if(!$order->has_status(Latitude_Checkout_Constants::WC_STATUS_ON_HOLD)) {
            return $this->handle_error("Order {$order_id} is not with on-hold status");
        }

        $order_amount = $this->to_price($order->get_total());
        if($order_amount < 0.1) {
            return $this->handle_error("Invalid amount {$amount}");
        }

        return [
            'merchantId' => $this->gateway->get_merchant_id(),
            'isTest' => $this->gateway->is_test_mode(),
            "gatewayReference" => (string)$order->get_meta(Latitude_Checkout_Constants::GATEWAY_REFERENCE),
            "merchantReference" => (string)$order_id,
            'amount' => $order_amount,
            'currency' => $order->get_currency(),
            "type" => self::TRANSACTION_TYPE_VOID,
            "description" => $desc,
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
