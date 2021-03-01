<?php

use Constants as LatitudeConstants; 

class Latitude_Gateway_Helper
{
    const PAY_INSTRUCTIONS = "";
   
    protected $_test_mode; 
    protected $_plugin_version;
    protected $_merchant_id;  
    protected $_base_currency;

    public function __construct(
        bool $test_mode,
        string $plugin_version,
        string $merchant_id 
    ) {
        $this->_test_mode = $test_mode;
        $this->_plugin_version = $plugin_version;
        $this->_merchant_id = $merchant_id; 
        $this->_base_currency = get_woocommerce_currency();
 
    }

    public function is_test_mode() {
        return $this->_test_mode;
    }

    public function get_plugin_version() {
        return $this->_plugin_version;
    }

    public function get_merchant_id() {
        return $this->_merchant_id;
    } 

    public function get_base_currency() {
        return $this->_base_currency;
    }

    public function get_payment_description() {

        if ($this->_test_mode) {
            $plugin_desc = 'TEST MODE ENABLED. ';
        } 
        return $plugin_desc;
    }


    public function get_HMAC($payload, $merchant_secret) { 
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

    public function generate_form($payment_request, $request_api_url) { 
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

    public function get_api_url() {
        $url = __( ($this->_test_mode ? LatitudeConstants::API_URL_TEST : LatitudeConstants::API_URL_PROD));
        return $url;
    }
               
    
    public function get_script_url() {
        $host = $this->_test_mode ? LatitudeConstants::CONTENT_HOST_TEST : LatitudeConstants::CONTENT_HOST_PROD;
        return $host. "/assets/content.js?platform=". LatitudeConstants::PLATFORM_TYPE ."&merchantId=". $this->_merchant_id;
    }

  
}