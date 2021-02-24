<?php

use Constants as LatitudeConstants;
use Latitude_Gateway_Helper as LatitudeHelper;

class Latitude_Payment_Request {

    protected $_helper; 

    public function __construct(
        LatitudeHelper $helper
    ) {
        $this->_helper =$helper;
    }
  
    public function create_request($order, $merchant_secret) { 

        $this->_helper->log(__("DEBUG message: create_request")); 

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
        $payment_request['x_url_cancel'] =  wc_get_checkout_url(); 
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
        $payment_request['x_shipping_amount'] = $this->_getShippingAmount($order_data); //round((float)$order_data['shipping_total'], $precision);  //$order_data['shipping_total'];
        $payment_request['x_tax_amount'] = $this->_getTaxAmount($order_data); //round((float)$order_data['total_tax'], $precision);  //$order_data['total_tax'];
        $payment_request['x_discount_amount'] = $this->_getDiscountAmount($order_data); //round((float)$order_data['discount_total'], $precision);  //$order_data['discount_total'];
     
        $payment_request['x_merchant_id'] = $this->_helper->get_merchant_id();
        $payment_request['x_test'] = $this->_helper->is_test_mode();

        $payment_request['x_platform_type'] = LatitudeConstants::PLATFORM_TYPE;
     
        $payment_request['x_platform_version'] = $this->_get_woocommerce_version();
        $payment_request['x_plugin_version'] = $this->_helper->get_plugin_version();
 
         $this->_helper->log(__("Payment Request (before signature): ". json_encode($payment_request))); 
        $signature = $this->_helper->get_HMAC($payment_request, $merchant_secret); //$this->_get_HMAC($payment_request); 
         $this->_helper->log(__("Payment Request signature: ". $signature)); 
        if (empty( $signature )) {
            throw new Exception(__("Invalid payment request validation. Merchant details missing." ));
        } 
        $payment_request['x_signature'] = $signature;
      
         $this->_helper->log(__("Payment Request: ". json_encode($payment_request))); 
        return $payment_request;        
    }


    public function generate_form_request($payment_request) {
        $this->_helper->log(__("DEBUG message: generate_form_request")); 
        $payment_request_url =  __( $this->_helper->get_api_url() . "/purchase");
        return $this->_helper->generate_form($payment_request, $payment_request_url);
    }

    private function _getShippingAmount($order_data)
    {
        if (isset($order_data['shipping_total'])) {
            return round((float)$order_data['shipping_total'], 2);;
        } 
        return 0;
    }

    private function _getTaxAmount($order_data)
    {
        if (isset($order_data['total_tax'])) {
            return round((float)$order_data['total_tax'], 2);
        } 
        return 0;
    }

    private function _getDiscountAmount($order_data)
    {
        if (isset($order_data['discount_total'])) {
            return round((float)$order_data['discount_total'], 2);;
        } 
        return 0;
    }


    private function _get_woocommerce_version() {
        global $woocommerce;
        return $woocommerce->version;
    }
    

}