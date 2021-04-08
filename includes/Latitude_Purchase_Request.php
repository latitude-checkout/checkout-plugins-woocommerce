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
     * Create the purchase request payload from cart
     *
     */
    public function create_payload_from_cart($cart, $post_id, $data) {

       
        $total = $cart->get_total( 'edit' ); 

        $payload = array();
        $payload['merchantId'] = $this->gateway->get_merchant_id();
        $payload['merchantName'] = get_option('blogname');
        $payload['isTest'] = $this->gateway->get_test_mode();
        $payload['merchantReference'] = strval($post_id);
        $payload['amount'] =  floatval($total);
        $payload['currency'] = get_woocommerce_currency();
        $payload['promotionReference'] = '';

        $payload['customer'] = array( 
            'firstName' => $this->check_null($data['billing_first_name']),
            'lastName' => $this->check_null($data['billing_last_name']),
            'phone' => $this->check_null($data['billing_phone']),
            'email' => $this->check_null($data['billing_email'])
            ); 

        $billing_phone = $this->check_null($data['billing_phone']);
        $payload['billingAddress'] = array(
            'name' => $this->get_formatted_full_name($data['billing_first_name'],$data['billing_last_name']),
            'line1' => $this->check_null($data['billing_address_1']),
            'line2' => $this->check_null($data['billing_address_2']),
            'city' => $this->check_null($data['billing_city']), 
            'postcode' => $this->check_null($data['billing_postcode']),
            'state' => $this->check_null($data['billing_state']),
            'countryCode' => $this->check_null($data['billing_country']),
            'phone' => $billing_phone
        ); 

        $methods_without_shipping_arr = apply_filters( 'woocommerce_order_hide_shipping_address', array('local_pickup') );
        $shipping_required = false;

        $chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
        if (!empty($chosen_shipping_methods)) {
            foreach ($chosen_shipping_methods as $shipping_method_id) {
                $shipping_method_name = current(explode(':', $shipping_method_id));
                if (!in_array($shipping_method_name, $methods_without_shipping_arr)) {
                    $shipping_required = true;
                    break;
                }
            }
        }       

        if ($shipping_required) { 
            $name = $this->get_formatted_full_name($data['shipping_first_name'],$data['shipping_last_name']);
            if($name !="" && $data['shipping_address_1'] != ""){
                $payload['shippingAddress'] = array(
                    'name' => $name,
                    'line1' => $this->check_null($data['shipping_address_1']),
                    'line2' => $this->check_null($data['shipping_address_2']),
                    'city' => $this->check_null($data['shipping_city']), 
                    'postcode' => $this->check_null($data['shipping_postcode']),
                    'state' => $this->check_null($data['shipping_state']),
                    'countryCode' => $this->check_null($data['shipping_country']),
                    'phone' => $billing_phone
                );        
            }     
        }

     
        // order line items 
        $payload['orderLines'] = $this->create_order_lines_from_cart($cart); 
        $payload['merchantUrls'] = array(
            'cancel' => $this->build_cancel_request_url($post_id),
            'callback' => '',
            'complete' => $this->build_complete_request_url($post_id) 
        ); 

        $payload['totalDiscountAmount'] = floatval($cart->get_discount_total());
        $payload['totalShippingAmount'] = floatval($cart->get_shipping_total());
        $payload['totalTaxAmount'] = floatval($cart->get_total_tax()); 
        $payload['platformType'] = LatitudeConstants::WC_LATITUDE_GATEWAY_PLATFORM;
        $payload['platformVersion'] = WC()->version;
        $payload['pluginVersion'] = $this->gateway->get_plugin_version();
        return $payload;

    }

    /**
     * Builds the url callback after purchase request is confirmed
     *
     */
    private function build_complete_request_url($post_id) {
        $confirm_nonce = wp_create_nonce( "latitudecheckout_confirm_nonce-{$post_id}" ); 
        $home_url = __(
            get_home_url() . LatitudeConstants::CALLBACK_URL);

        $return_url = add_query_arg( array(
            'post_type' => 'latitudecheckout_order',
            'p' => $post_id,
            'nonce' => $confirm_nonce 
        ),   $home_url);

        return $return_url;
    }

    /**
     * Builds the url callback when purchase request is cancelled or returned to cart
     *
     */
    private function build_cancel_request_url($post_id) { 
        return WC()->cart->get_cart_url();  
    }

    /**
     * Builds the order lines for the purchase request payload
     *
     */
    private function create_order_lines_from_cart($cart) {
 
        $order_lines = [];
        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            $product = $cart_item['data']; 
            $itemTotal = $cart_item['line_total']; //$cart->get_product_subtotal( $product, $cart_item['quantity'] );
            $itemTotalTax =   $cart_item['line_tax'];
            $shipping_required = empty($product->get_shipping_class_id())
            ? false
            : true;

            $order_line = [
                'name' => $product->get_name(),
                'productUrl' =>$product->get_permalink(), 
                'sku' =>  $product->get_sku(),
                'quantity' => $cart_item['quantity'],
                'unitPrice' => floatval($product->get_price()),
                'amount' => floatval($itemTotal),
                'tax' => floatval($itemTotalTax),
                'requiresShipping' => $shipping_required,
                'isGiftCard' => false, //TODO
            ];
            array_push($order_lines, $order_line);
        } 
        return $order_lines;
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
     * Formats customer name
     *
     */ 
    private function get_formatted_full_name($firstname, $lastname) {
        if (!empty($firstname) && !empty($lastname)) {
            $name = $firstname . ' ' . $lastname;
        } elseif (!empty($firstname)) {
            $name = $firstname;
        } elseif (!empty($lastname)) {
            $name = $lastname;
        } else {
            $name = '';
        }
        return $name;
    }
}
