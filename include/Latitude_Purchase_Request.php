<?php

use Constants as LatitudeConstants;

class Latitude_Purchase_Request
{
    protected $logger = null;
    protected $gateway;

    public function __construct()
    {
        $this->gateway = WC_LatitudeCheckoutGateway::get_instance();
    }

    private function log($message)
    {
        if ($this->logger === null) {
            $this->logger = wc_get_logger();
        }
        $this->logger->debug($message, ['source' => 'latitude_checkout']);
    }

    public function build_parameters($order_id)
    {
        $order = wc_get_order($order_id);

        $order_lines = $this->build_order_lines($order);
        $payment_request = [
            'merchantId' => $this->gateway->get_merchant_id(),
            'merchantName' => get_option('blogname'),
            'isTest' => $this->gateway->get_test_mode(),
            'merchantReference' => strval($order_id),
            'amount' => floatval($order->get_total()),
            'currency' => $order->get_currency(),
            'promotionReference' => '',
            'customer' => [
                'firstName' => $order->get_billing_first_name(),
                'lastName' => $order->get_billing_last_name(),
                'phone' => $order->get_billing_phone(),
                'email' => $order->get_billing_email(),
            ],
            'billingAddress' => [
                'name' => $order->get_formatted_billing_full_name(),
                'line1' => $order->get_billing_address_1(),
                'line2' => $order->get_billing_address_2(),
                'city' => $order->get_billing_city(),
                'postcode' => $order->get_billing_postcode(),
                'state' => $order->get_billing_state(),
                'countryCode' => $order->get_billing_country(),
                'phone' => $order->get_billing_phone(),
            ],
            'shippingAddress' => [
                'name' => $order->get_formatted_shipping_full_name(),
                'line1' => $order->get_shipping_address_1(),
                'line2' => $order->get_shipping_address_2(),
                'city' => $order->get_shipping_city(),
                'postcode' => $order->get_shipping_postcode(),
                'state' => $order->get_shipping_state(),
                'countryCode' => $order->get_shipping_country(),
                'phone' => $order->get_billing_phone(),
            ],
            'orderLines' => $order_lines,
            'merchantUrls' => [
                'cancel' => WC()->cart->get_checkout_url(),
                'callback' => '',
                'complete' => __(
                    get_site_url() . LatitudeConstants::COMPLETE_ROUTE
                ),
            ],
            'totalDiscountAmount' => floatval($order->get_total_discount()),
            'totalShippingAmount' => floatval($order->get_shipping_total()),
            'totalTaxAmount' => floatval($order->get_total_tax()),
            'platformType' => LatitudeConstants::WC_LATITUDE_GATEWAY_PLATFORM,
            'platformVersion' => WC()->version,
            'pluginVersion' => LatitudeConstants::WC_LATITUDE_GATEWAY_VERSION,
        ];
        return $payment_request;
    }

    public function build_order_lines($order)
    {
        $order_lines = [];
        foreach ($order->get_items() as $key => $item):
            $product = $item->get_product();

            $shipping_required = !empty($product->get_shipping_class())
                ? false
                : true;
            $order_line = [
                'name' => $item->get_name(),
                'productUrl' => $product->get_permalink(),
                'sku' => $product->get_sku(),
                'quantity' => $item->get_quantity(),
                'unitPrice' => floatval($product->get_price()),
                'amount' => floatval($item->get_total()),
                'tax' => floatval($item->get_total_tax()),
                'requiresShipping' => $shipping_required,
                'isGiftCard' => false, //TODO
            ];
            array_push($order_lines, $order_line);
        endforeach;

        return $order_lines;
    }

    private function floatval($number)
    {
        return number_format((float) $number, 2, '.', '');
    }

    private function get_woocommerce_version()
    {
        global $woocommerce;
        return $woocommerce->version;
    }
}
