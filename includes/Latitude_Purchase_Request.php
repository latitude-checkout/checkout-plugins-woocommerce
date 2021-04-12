<?php
/**
 * Latitude Checkout Payment Request Handler Class
 */

use Constants as LatitudeConstants;

class Latitude_Purchase_Request
{
    /**
     * Protected variables.
     *
     * @var		WC_LatitudeCheckoutGateway	$gateway		A reference to the WooCommerce Latitude Checkout Payment Gateway.
     */
    protected $gateway;

    /**
     * Class constructor. Called when an object of this class is instantiated.
     *
     */
    public function __construct()
    {
        $this->gateway = WC_LatitudeCheckoutGateway::get_instance();
    }

/**
     * Builds the purchase request payload
    *
    */
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
            'billingAddress' => $this->get_billing_address($order),
            'shippingAddress' => $this->get_shipping_address($order),
            'orderLines' => $order_lines,
            'merchantUrls' => [
                'cancel' => $order->get_cancel_order_url_raw(), //$this->build_cancel_request_url($order_id),
                'callback' => '',
                'complete' => $this->build_complete_request_url() 
            ],
            'totalDiscountAmount' => floatval($order->get_total_discount()),
            'totalShippingAmount' => floatval($order->get_shipping_total()),
            'totalTaxAmount' => floatval($order->get_total_tax()),
            'platformType' => LatitudeConstants::WC_LATITUDE_GATEWAY_PLATFORM,
            'platformVersion' => WC()->version,
            'pluginVersion' => $this->gateway->get_plugin_version(),
        ]; 
        return $payment_request;
    }
  
    /**
     * Builds the order lines for the purchase request payload
    *
    */
    private function build_order_lines($order)
    {
        $order_lines = [];
        foreach ($order->get_items() as $key => $item):
            $product = $item->get_product(); 
            $shipping_class = $product->get_shipping_class();
            $shipping_required = isset($shipping_class) ? true : false;

            $this->gateway::log_debug(sprintf(
                __(
                    'product: %s,  shipping_class: %s, shipping_required: %d',
                    'woo_latitudecheckout'
                ),
                $item->get_name(), $product->get_shipping_class(), $shipping_required
            ));

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

    /**
     * Builds the url callback after purchase request is confirmed
     *
     */
    private function build_complete_request_url() { 
        $return_url = __(
            get_home_url() . LatitudeConstants::CALLBACK_URL); 
        return $return_url;
    } 

    
 
    /**
     * Formats floating value
     *
     */
    private function floatval($number)
    {
        return number_format((!empty($number)?$number:0), 2, '.', ''); 
    }

    /**
     * Check null value for data
     *
     */
    private function check_null($value,$default_value="")
    {

        return is_null($value)?$default_value:$value;
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
      
        if($order->get_shipping_first_name() == '' || $order->get_shipping_address_1() == '' ||  wc_ship_to_billing_address_only())
        { 
            $shipping_address = $this->get_billing_address($order);
        } else { 
            $shipping_address =   array(
                'name' => $order->get_formatted_shipping_full_name(),
                'line1' => $order->get_shipping_address_1(),
                'line2' => $order->get_shipping_address_2(),
                'city' => $order->get_shipping_city(),
                'postcode' => $order->get_shipping_postcode(),
                'state' => $order->get_shipping_state(),
                'countryCode' => $order->get_shipping_country(),
                'phone' => $order->get_billing_phone(),
            );           
        
           
        }
        $this->gateway::log_debug( wp_json_encode($shipping_address));  
        return $shipping_address;
    }

}
