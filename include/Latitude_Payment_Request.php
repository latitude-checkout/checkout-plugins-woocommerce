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

    public function build_request_parameters($order_id, $merchant_id, $is_test) { 

        $this->log("build_request_parameters");
        if(!$order_id) {
            // log error for null order_id
            return;
        }

        // //TODO: check if only logged in users are allowed
        // if (!is_user_logged_in()) {
        //     // to exit or notify if user is logged or not
        //     return;
        // }

        $this->log("constructing payload .. ");
        global $woocommerce;
        $order = new WC_Order( $order_id );   
        $order_lines = $this->build_order_lines($order); 
        $payment_request = array (
            "merchantId"            => $merchant_id,
            "merchantName"          => $merchant_id,
            "isTest"                => $is_test,
            "merchantReference"    => $order_id,
            "amount"                => $order->get_total(),
            "currency"              => $order->get_currency(),
            "promotionReference"   => "",
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
            "totalDiscountAmount" =>  $order->get_total_discount(),       
            "totalShippingAmount" =>  $order->get_total_discount(),                                            
            "totalTaxAmount"      =>  $order->get_total_tax(), 
            "platformType"        =>  LatitudeConstants::WC_LATITUDE_GATEWAY_PLATFORM,
            "platformVersion"     =>  $this->get_woocommerce_version(),
            "pluginVersion"        =>  LatitudeConstants::WC_LATITUDE_GATEWAY_VERSION,
        );  
        return $payment_request;
    }

    public function build_order_lines($order) { 

        $order_lines = array(); 
        foreach ($order->get_items() as $key => $item ):
            $product      = $item->get_product();
            
            $shipping_required = (!empty($product->get_shipping_class())) ? "false" : "true";  
            $order_line = array( 
                "name" => $item->get_name(),
                "productUrl" => $product->get_permalink(),
                "sku" => $product->get_sku(),
                "quantity" => $item->get_quantity(),
                "unitPrice" => $product->get_price(),
                "amount" => $item->get_total(),
                "tax" => $item->get_total_tax(),
                "requiresShipping" => $shipping_required,
                "isGiftCard" => ""
            ); 
            array_push($order_lines, $order_line);
        endforeach; 
        
        return $order_lines;
    }
  
    public function create_request($order, $merchant_id, $merchant_secret, $is_test) { 
 

        if (is_null($this->_helper)) {
            throw new Exception("This payment gateway cannot proceed to process this request.");
        }
        $precision = 2;

        $order_id = $order->get_id();  
        $order_data = $order->get_data();

        $payment_request['x_currency'] =$order_data['currency'];
        if (!in_array($payment_request['x_currency'], LatitudeConstants::ALLOWED_CURRENCY)) {
            throw new Exception(__("Unsupported currency ". $payment_request["x_currency"])); 
        }        
    
        $payment_request['x_amount'] =  round((float)$order_data['total'], $precision);  //$order_data['total'];
        $payment_request['x_customer_first_name'] =  $order_data['billing']['first_name'];
        $payment_request['x_customer_last_name'] = $order_data['billing']['last_name'];
        $payment_request['x_customer_phone'] = $order_data['billing']['phone'];
        $payment_request['x_customer_email'] = $order_data['billing']['email'];

        $payment_request['x_merchant_reference'] = $order_id; //TODO not sure what to specify here??? 

        //TODO check if correct URLs
        $payment_request['x_url_cancel'] =  WC()->cart->get_checkout_url(); //wc_get_checkout_url(); 
        $payment_request['x_url_callback'] =  __( get_site_url(). LatitudeConstants::CALLBACK_ROUTE ) ; //TODO  $this->response_callback_url;
        $payment_request['x_url_complete'] = $order->get_checkout_order_received_url(); //$this->get_return_url($order);   

        $payment_request['x_billing_name'] = $order_data['billing']['first_name'] . ' ' . $order_data['billing']['last_name'];
        $payment_request['x_billing_line1'] = $order_data['billing']['address_1'];
        $payment_request['x_billing_line2'] = $order_data['billing']['address_2'];
        $payment_request['x_billing_area1'] = $order_data['billing']['city'];
        $payment_request['x_billing_postcode'] = $order_data['billing']['postcode'];
        $payment_request['x_billing_region'] = $order_data['billing']['state'];
        $payment_request['x_billing_country_code'] = $order_data['billing']['country'];
        $payment_request['x_billing_phone'] = $order_data['billing']['phone'];

        if (!empty($order_data['shipping']) && !empty($order_data['shipping']['address_1'])) {
            $payment_request['x_shipping_name'] = $order_data['shipping']['first_name']. ' ' .$order_data['shipping']['last_name'];
            $payment_request['x_shipping_line1'] = $order_data['shipping']['address_1'];
            $payment_request['x_shipping_line2'] = $order_data['shipping']['address_2'];
            $payment_request['x_shipping_area1'] = $order_data['shipping']['city'];
            $payment_request['x_shipping_postcode'] = $order_data['shipping']['postcode'];
            $payment_request['x_shipping_region'] = $order_data['shipping']['state'];
            $payment_request['x_shipping_country_code'] = $order_data['shipping']['country'];
            $payment_request['x_shipping_phone'] = $order_data['billing']['phone']; //TODO current template has no shipping phone number field
        } else { 
            $payment_request['x_shipping_name'] = $order_data['billing']['first_name'] . ' ' . $order_data['billing']['last_name'];
            $payment_request['x_shipping_line1'] = $order_data['billing']['address_1'];
            $payment_request['x_shipping_line2'] = $order_data['billing']['address_2'];
            $payment_request['x_shipping_area1'] = $order_data['billing']['city'];
            $payment_request['x_shipping_postcode'] = $order_data['billing']['postcode'];
            $payment_request['x_shipping_region'] = $order_data['billing']['state'];
            $payment_request['x_shipping_country_code'] = $order_data['billing']['country'];
            $payment_request['x_shipping_phone'] = $order_data['billing']['phone'];
              
        }

        // Iterating through each WC_Order_Item_Product objects
        $index = 0;
        foreach ($order->get_items() as $key => $item ):
            $product      = $item->get_product();
            $payment_request['x_lineitem_' . $index . '_name'] =  $item->get_name();
            $payment_request['x_lineitem_' . $index . '_image_url'] = ""; // $product->get_permalink();
            $payment_request['x_lineitem_' . $index . '_sku'] = $product->get_sku();
            $payment_request['x_lineitem_' . $index . '_quantity'] = $item->get_quantity();
            $payment_request['x_lineitem_' . $index . '_unit_price'] = $product->get_price(); // discounted price
            $payment_request['x_lineitem_' . $index . '_amount'] = round((float)$item->get_total(), $precision);
            $payment_request['x_lineitem_' . $index . '_tax'] = $item->get_total_tax();

            $payment_request['x_lineitem_' . $index . '_requires_shipping'] = "false"; 
            if(!empty($product->get_shipping_class())) { 
                $payment_request['x_lineitem_' . $index . '_requires_shipping'] = "true";  
            }
            $payment_request['x_lineitem_' . $index . '_gift_card'] = "false"; // TODO;        
            $index++;
        endforeach;

        
        $payment_request['x_line_item_count'] = $index;
        $payment_request['x_shipping_amount'] = $order->get_shipping_total();  
        $payment_request['x_tax_amount'] = $order->get_total_tax();  
        $payment_request['x_discount_amount'] = $order->get_discount_total(); 
     
        $payment_request['x_merchant_id'] = $merchant_id;
        $payment_request['x_test'] = $is_test;

        $payment_request['x_platform_type'] = LatitudeConstants::WC_LATITUDE_GATEWAY_PLATFORM;
     
        $payment_request['x_platform_version'] = $this->get_woocommerce_version();
        $payment_request['x_plugin_version'] = LatitudeConstants::WC_LATITUDE_GATEWAY_VERSION;
  
        $signature = $this->get_HMAC($payment_request, $merchant_secret); //$this->_get_HMAC($payment_request); 
        if (empty( $signature )) {
            throw new Exception(__("Invalid payment request validation. Merchant details missing." ));
        } 
        $payment_request['x_signature'] = $signature;  
        return $payment_request;        
    }


    public function generate_form_request($payment_request) { 
        $payment_request_url =  __( $this->get_api_url() . "/purchase");
        return $this->generate_form($payment_request, $payment_request_url);
    }
  

    private function get_woocommerce_version() {
        global $woocommerce;
        return $woocommerce->version;
    }
 
    private function get_HMAC($payload, $merchant_secret) { 
        $message = ""; 
        if (!is_array($payload)) {
            return "";
        }

        $secret = $merchant_secret;
        if (!isset($secret)) {
            return "";
        }

        ksort($payload);

        foreach ($payload as $key => $value) {
            if (('x_url_complete' === $key) || ('x_url_cancel' === $key) || ('x_url_callback' === $key)) {
                $value = htmlspecialchars_decode($value);
            }         
            $message .= $key . $value;
        }
 
        return hash_hmac("sha256", $message, utf8_encode($secret));        
    } 

    private function get_api_url() {
        $url = __( ($this->_test_mode ? LatitudeConstants::API_URL_TEST : LatitudeConstants::API_URL_PROD));
        return $url;
    }    


    private function generate_form($payment_request, $request_api_url) { 
        //convert $payment_request to form elements 

        $payment_request_form_tags = array();
        foreach ($payment_request as $key => $value) {
			$payment_request_form_tags[] = '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
		}    

        //<input type="submit" class="button-alt" id="submit_latitude_payment_form" value="' . self::PAY_INSTRUCTIONS . '" />
        $form_str = '<form action="' . esc_url($request_api_url) . '" method="post" id="latchkout_payment_form">
				' . implode('' . PHP_EOL, $payment_request_form_tags) . '
                <input type="submit" class="button-alt" id="submit_latitude_payment_form" value="' . self::PAY_INSTRUCTIONS . '" />
                				<script type="text/javascript">
                					jQuery(function(){
                						jQuery("body").block(
                							{
                								message: "' . __('Thank you for your order. We are now redirecting you to Latitude Checkout to make payment.', 'latitude-checkout-gateway') . '",
                								overlayCSS:
                								{
                									background: "#fff",
                									opacity: 0.6
                								},
                								css: {
                									padding:        20,
                									textAlign:      "center",
                									color:          "#555",
                									border:         "3px solid #aaa",
                									backgroundColor:"#fff",
                									cursor:         "wait"
                								}
                							});
                						jQuery( "#submit_latitude_payment_form" ).click();
                                    });  
                    </script>              
            </form>'; 
        return $form_str;
    }
}