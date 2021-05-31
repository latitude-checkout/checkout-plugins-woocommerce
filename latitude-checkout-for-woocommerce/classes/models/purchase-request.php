<?php
/**
 * Latitude Checkout Purchase Request Data Factory Class
 */
if ( ! defined( 'ABSPATH' ) ) {
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
         $order = wc_get_order($order_id);

        $order_lines = $this->get_order_lines($order); 
        $payload = array(
            'merchantId' => $this->gateway->get_merchant_id(),
            'merchantName' => get_option('blogname'),
            'isTest' => $this->gateway->is_test_mode(),
            'merchantReference' => strval($order_id),
            'amount' => $this->floatval($order->get_total()),
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
                'cancel' => $order->get_cancel_order_url_raw(),  
                'callback' => '',
                'complete' => $this->get_complete_callback_url() 
            ],
            'totalDiscountAmount' => $this->floatval($order->get_total_discount()),
            'totalShippingAmount' => $this->get_order_shipping_amount($order), 
            'platformType' => 'woocommerce',
            'platformVersion' => WC()->version,
            'pluginVersion' => $this->gateway->get_plugin_version(),
        ); 
        return $payload;
    }
  
    /**
     * Builds the order lines for the purchase request payload
    *
    */
    private function get_order_lines($order)
    {
        $inc_tax = true; 
        $round   = false; 

        $order_lines = [];
        foreach ($order->get_items() as $key => $item):
            $product = $item->get_product(); 
            $shipping_class = $product->get_shipping_class();
            $shipping_required = isset($shipping_class) ? true : false;  
            
            $is_gift_card = 'coupon' === $item->get_type() ? true : false;
            $order_line = array(
                'name' => $item->get_name(), 
                'quantity' => $item->get_quantity(),
                'unitPrice' => $this->get_item_unit_price($item),
                'amount' => $this->get_item_total_amount($item), 
                'requiresShipping' => $shipping_required,
                'isGiftCard' => $is_gift_card, 
            );
            array_push($order_lines, $order_line);
        endforeach;

        return $order_lines;
    }

   
    /**
     * Compute total line item amount
     *
     */
    private function get_item_total_amount($order_item) {
 
        $order_item_total_amount = ($this->floatval($order_item->get_total()) + $this->floatval($order_item->get_total_tax())) * 100 ;
     	return $this->floatval( $order_item_total_amount / 100 );
    } 
  
    /**
     * Get item unit price  
     *
     */
    private function get_item_unit_price($order_item) {
 
        $item_subtotal = (($this->floatval($order_item->get_total()) + $this->floatval($order_item->get_total_tax())) / $order_item->get_quantity() ) * 100;
        return $this->floatval( $item_subtotal / 100);
    } 
    
    
    /**
     * Compute total shipping amount
     *
     */
    private function get_order_shipping_amount($order) {
 
        $total_shipping_amount = $order->get_shipping_total() + $order->get_shipping_tax(); 
		return $this->floatval($total_shipping_amount);
    } 


    /**
     * Builds the url callback after purchase request is confirmed
     *
     */
    private function get_complete_callback_url() {  
        $return_url = __(
            get_home_url() . '/wc-api/lc-purchase-complete' ); 
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
        return $shipping_address;
    } 

}
