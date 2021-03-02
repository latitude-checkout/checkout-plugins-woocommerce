<?php

use Constants as LatitudeConstants; 

class Latitude_Payment_Request { 
 
 
    protected $logger = null;

    public function __construct(  
    ) { 
    }
      

    private function log($message) {
        if ($this->logger === null) {
            $this->logger = wc_get_logger();
        }
        $this->logger->debug($message, array('source' => 'latitude_checkout'));
    }

    public function get_api_url( $is_test) {
        $url = __( ( $is_test ? LatitudeConstants::API_URL_TEST : LatitudeConstants::API_URL_PROD) . "/purchase");
        return $url;
    }  

    public function build_request_parameters($order_id, $merchant_id, $is_test) { 

        global $woocommerce;
        $order = new WC_Order( $order_id );     

        // //TODO: check if only logged in users are allowed
        // if (!is_user_logged_in()) {
        //     // to exit or notify if user is logged or not
        //     return;
        // }
 
        $order_lines = $this->build_order_lines($order);  
        $payment_request = array (
            "merchantId"            => $merchant_id,
            "merchantName"          => "wootest",  // TODO: get dynamically
            "isTest"                => $is_test,
            "merchantReference"     => strval($order_id),  
            "amount"                => floatval($order->get_total()),
            "currency"              => $order->get_currency(),
            "promotionReference"    => "",
            "customer"              => array (
                                        "firstName" =>  $order->get_billing_first_name(),
                                        "lastName"  =>  $order->get_billing_last_name(),
                                        "phone"     =>  $order->get_billing_phone(),
                                        "email"     =>  $order->get_billing_email()
                                    ),
            "billingAddress"       => array (
                                        "name"          =>   $order->get_formatted_billing_full_name(),
                                        "line1"         =>   $order->get_billing_address_1(),
                                        "line2"         =>   $order->get_billing_address_2(),
                                        "city"          =>   $order->get_billing_city(),
                                        "postcode"      =>   $order->get_billing_postcode(),
                                        "state"         =>   $order->get_billing_state(),
                                        "countryCode"   =>   $order->get_billing_country(),
                                        "phone"         =>   $order->get_billing_phone()
                                    ),
            "shippingAddress"      => array (
                                        "name"          =>   $order->get_formatted_shipping_full_name(),
                                        "line1"         =>   $order->get_shipping_address_1(),
                                        "line2"         =>   $order->get_shipping_address_2(),
                                        "city"          =>   $order->get_shipping_city(),
                                        "postcode"      =>   $order->get_shipping_postcode(),
                                        "state"         =>   $order->get_shipping_state(),
                                        "countryCode"   =>   $order->get_shipping_country(),
                                        "phone"         =>   $order->get_billing_phone()
                                    ),   
            "orderLines"           => $order_lines,
            "merchantUrls"         => array(
                                        "cancel"        =>  WC()->cart->get_checkout_url(),
                                        "callback"      => __( get_site_url(). LatitudeConstants::CALLBACK_ROUTE ),
                                        "complete"      => $order->get_checkout_order_received_url()
                                    ), 
            "totalDiscountAmount" =>  floatval($order->get_total_discount()),       
            "totalShippingAmount" =>  floatval($order->get_shipping_total()),                                            
            "totalTaxAmount"      =>  floatval($order->get_total_tax()), 
            "platformType"        =>  LatitudeConstants::WC_LATITUDE_GATEWAY_PLATFORM,
            "platformVersion"     =>  $this->get_woocommerce_version(),
            "pluginVersion"       =>  LatitudeConstants::WC_LATITUDE_GATEWAY_VERSION,
        );  
        return $payment_request;
    }

    public function build_order_lines($order) { 

        $order_lines = array(); 
        foreach ($order->get_items() as $key => $item ):
            $product      = $item->get_product();
            
            $shipping_required = (!empty($product->get_shipping_class())) ? false : true;  
            $order_line = array( 
                    "name"              => $item->get_name(),
                    "productUrl"        => $product->get_permalink(),
                    "sku"               => $product->get_sku(),
                    "quantity"          => $item->get_quantity(),
                    "unitPrice"         => floatval($product->get_price()),
                    "amount"            => floatval($item->get_total()),
                    "tax"               => floatval($item->get_total_tax()),
                    "requiresShipping"  => $shipping_required,
                    "isGiftCard"        => false, //TODO
            ); 
            array_push($order_lines, $order_line);
        endforeach; 

        return $order_lines;
    }
   
    private function floatval($number) {
        return number_format((float)$number, 2, '.', '');
    }

    private function get_woocommerce_version() {
        global $woocommerce;
        return $woocommerce->version;
    } 
     
}