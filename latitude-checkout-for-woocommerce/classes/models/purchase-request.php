<?php

/**
 * Latitude Checkout Purchase Request Data Factory Class
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Latitude_Checkout_Purchase_Data_Factory
{
    /**
     * Protected variables.
     *
     * @var		WC_Latitude_Checkout_Gateway	$gateway		A reference to the WooCommerce Latitude Checkout Payment Gateway.
     */
    protected $gateway;

    /**
     * Class constructor. Called when an object of this class is instantiated.
     *
     */
    public function __construct()
    {
        $this->gateway = WC_Latitude_Checkout_Gateway::get_instance();
    }

    /**
     * Builds the purchase request payload
     *
     */
    public function get_payload($order_id)
    {
        $order = $this->gateway->get_valid_order($order_id);
        if ($order == null) {
            return array();
        }

        $order_lines = $this->get_order_lines($order);
        $payload = array(
            'merchantId' => $this->gateway->get_merchant_id(),
            'merchantName' => $this->get_shop_name(),
            'isTest' => $this->gateway->is_test_mode(),
            'merchantReference' => strval($order_id),
            'amount' => $this->floatval($order->get_total()),
            'currency' => $order->get_currency(),
            'customer' => [
                'firstName' => $order->get_billing_first_name(),
                'lastName' => $order->get_billing_last_name(),
                'phone' => $order->get_billing_phone(),
                'email' => $order->get_billing_email(),
            ],
            'billingAddress' => $this->get_billing_address($order),
            'shippingAddress' => $this->get_shipping_address($order),
            'orderLines' => $order_lines,
            'merchantUrls' => [
                'cancel' => $order->get_cancel_order_url_raw(),
                'complete' => $this->get_complete_callback_url()
            ],
            'totalDiscountAmount' => $this->get_total_discount_amount($order),
            'totalShippingAmount' => $this->get_total_shipping_amount($order),
            'platformType' => Latitude_Checkout_Constants::PLATFORM_NAME,
            'platformVersion' => WC()->version,
            'pluginVersion' => $this->gateway->get_plugin_version(),
        );
        return $payload;
    }


    private function get_shop_name()
    {
        $name = get_option('blogname');
        if (!is_null($name) && !empty($name)) {
            return $name;
        }

        return get_option('home');
    }

    /**
     * Builds the order lines for the purchase request payload
     *
     */
    private function get_order_lines($order)
    {  
        $order_lines = [];
        foreach ($order->get_items() as $key => $item) :
            $product = $item->get_product();
            $unit_price = wc_get_price_including_tax($product);
            $order_line = array(
                'name' => $item->get_name(),
                'productUrl' => $product->get_permalink(),
                'sku' => $product->get_sku(),
                'quantity' => $item->get_quantity(),
                'unitPrice' => $unit_price,
                'amount' => $this->floatval($unit_price * $item->get_quantity()),
                'requiresShipping' => $this->is_shipping_required($product),
                'isGiftCard' => $this->is_gift_card($item),
            );
            array_push($order_lines, $order_line);
        endforeach;

        return $order_lines;
    }

    private function is_gift_card($item)
    {
        return in_array(Latitude_Checkout_Constants::WC_GIFT_CARD_ITEM_TYPES, $item->get_type());
    }

    private function is_shipping_required($product)
    {
        $shipping_class = $product->get_shipping_class();
        return isset($shipping_class);
    }

    /**
     * Compute total shipping amount
     *
     */
    private function get_total_shipping_amount($order)
    {
        return $this->floatval($order->get_shipping_total() + $order->get_shipping_tax());
    }


    /**
     * Compute total discount amount
     *
     */
    private function get_total_discount_amount($order)
    {
        return $this->floatval($order->get_discount_total() + $order->get_discount_tax());
    }


    /**
     * Builds the url callback after purchase request is confirmed
     *
     */
    private function get_complete_callback_url()
    {
        $return_url = __(
            get_home_url() . '/wc-api/lc-purchase-complete'
        );
        return $return_url;
    }

    /**
     * Formats floating value
     *
     */
    private function floatval($number)
    {
        return number_format((!empty($number) ? $number : 0), 2, '.', '');
    }


    /**
     * Returns billing details
     *
     */
    private function get_billing_address($order)
    {
        $billing_address =   array(
            'name' => $order->get_formatted_billing_full_name(),
            'line1' => $order->get_billing_address_1(),
            'line2' => $order->get_billing_address_2(),
            'city' => $order->get_billing_city(),
            'postcode' => $order->get_billing_postcode(),
            'state' => $order->get_billing_state(),
            'countryCode' => $order->get_billing_country(),
            'phone' => $order->get_billing_phone(),
        );
        return $billing_address;
    }


    /**
     * Returns shipping details
     *
     */
    private function get_shipping_address($order)
    {
        if ($order->get_shipping_first_name() == '' || $order->get_shipping_address_1() == '' ||  wc_ship_to_billing_address_only()) {
            return $this->get_billing_address($order);
        }

        $shipping_address = array(
            'name' => $order->get_formatted_shipping_full_name(),
            'line1' => $order->get_shipping_address_1(),
            'line2' => $order->get_shipping_address_2(),
            'city' => $order->get_shipping_city(),
            'postcode' => $order->get_shipping_postcode(),
            'state' => $order->get_shipping_state(),
            'countryCode' => $order->get_shipping_country(),
            'phone' => $order->get_billing_phone(),
        );
        return $shipping_address;
    }
}
