<?php

use Constants as LatitudeConstants; 
use Latitude_Gateway_Helper as LatitudeHelper;
use Latitude_Payment_Banner as LatitudeGatewayBanner;
use Latitude_Payment_Request as LatitudePaymentRequest;

// references:    
// https://github.com/bekarice/woocommerce-gateway-offline/blob/master/woocommerce-gateway-offline.php

class WC_Latitude_Gateway extends WC_Payment_Gateway {

    protected $_helper;
    protected $_checkout_banner;
    protected $_payment_request;

    protected $plugin_name; 
    protected $plugin_version;   
    protected $myactionhooks;
 
    public function __construct() { 

        if ( defined( 'WC_LATITUDE_GATEWAY_VERSION' ) ) {
            $this->plugin_version = WC_LATITUDE_GATEWAY_VERSION;
        } else {
            $this->plugin_version = '0.0.1';
        }
        $this->id = LatitudeConstants::PAYMENT_PLUGIN_ID; // payment gateway plugin ID
        $this->plugin_name = 'latitude-checkout';  

        $plugin_title = 'Latitude Checkout';  // admin-settings-Method title
        $plugin_method_desc = 'Use Latitude as payment method for WooCommerce orders.'; 
        // if ( 'NZD' == get_woocommerce_currency()) {
        //     $plugin_title = 'GEM Finance Interest Free'; 
        //     $plugin_method_desc = 'Use GEM Finance Interest Free as payment for WooCommerce orders.'; 
        // }

       
        $this->icon = apply_filters('woocommerce_gateway_icon', 10, 2 );  
        $this->has_fields = true; // in case you need a customform
        $this->method_title =  _ ( $plugin_title, 'latitude-gateway');
        $this->method_description = _( $plugin_method_desc, 'latitude-gateway');
        $this->title =  _ ($plugin_title, 'latitude-gateway');   
         

        $this->init_form_fields();
        $this->init_settings(); 
        $this->merchant_id = $this->get_option('merchant_id');
        $this->merchant_secret = $this->get_option('merchant_secret');
        $this->test_mode = 'yes' === $this->get_option( 'testmode' );
   
        
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_filter('woocommerce_gateway_icon', array($this,'latitude_gateway_icon'), 10, 2);
        add_filter('woocommerce_order_button_text', array($this, 'update_button_text') );

        // // TO Do if custom JavaScript is needed 
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
       
        // You can also register a webhook here
        // add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );      
        add_action( "woocommerce_receipt_{$this->id}" , array($this, 'receipt_page'), 10, 1);  

        $this->_helper = new LatitudeHelper($this->test_mode, $this->plugin_version, $this->merchant_id);
        $this->_payment_request = new LatitudePaymentRequest($this->_helper); 
        $this->_checkout_banner = new LatitudeGatewayBanner($this->_helper); 
    }

    public function init_form_fields(){
         
        $this->form_fields =  array(           
            'merchant_id' => array(
                'title'       => _('Merchant ID', 'latitude-gateway'),
                'type'        => 'text',
                'description' => __('Merchant ID provided by Latitude', 'latitude-gateway'),
                'desc_tip'    => false,
                'default'     => 'MerchantID',
            ),
            'merchant_secret' => array(
                'title'       => _('Merchant Secret Key', 'latitude-gateway'),
                'type'        => 'password',
                'description' => __('Merchant Secret Key provided by Latitude', 'latitude-gateway'),
                'desc_tip'    => false,
                'default'     =>  'MerchantSecretKey',
            ),                
            'enabled' => array(
                'title'       => __('Enable/Disable', 'latitude-gateway'),
                'label'       => __('Is Enabled?', 'latitude-gateway'),
                'type'        => 'checkbox',
                'description' => __('Enable Latitude Checkout Payment Gateway', 'latitude-gateway'),
                'desc_tip'    => false,
                'default'     => 'yes',
            ),
            'testmode' => array(
                'title'       => __('Test mode', 'latitude-gateway'),
                'label'       => __('Is Test Mode?', 'latitude-gateway'),
                'type'        => 'checkbox',
                'description' => __('Place the payment gateway in test mode using test API keys.', 'latitude-gateway'),
                'default'     => 'yes',
                'desc_tip'    => false,
            ));
    }    

 
    public function update_button_text($order_button_text) {   
    
        $current_payment_method     = WC()->session->get('chosen_payment_method'); // The chosen payment  
        // For matched payment(s) method(s), we remove place order button (on checkout page) 
        if(  $current_payment_method == Constants::PAYMENT_PLUGIN_ID ) { 
            $order_button_text = 'Choose a Plan';
        }  
        return $order_button_text;
    }

    public function enqueue_scripts() {    

        wp_enqueue_script( 'latitude_payment_fields_js', plugins_url( '../js/latitude-payment-fields.js', __FILE__ ), array('jquery') );     
    }   

    function latitude_gateway_icon( $icon, $gateway_id ) {
   
        if ( LatitudeConstants::PAYMENT_PLUGIN_ID == $gateway_id) {
            $icon =  __( '<img src="https://assets.latitudefinancial.com/merchant-services/latitude/icon/latitude-interest-free.svg" alt="Latitude Interest Fee" />');  
            if ( 'NZD' == get_woocommerce_currency() ) { 
                $icon = __( '<img src="https://assets.latitudefinancial.com/merchant-services/latitude/icon/gem-interest-free.svg" alt="GEM Interest Fee" />');
            }        
        } 
        return $icon;  
    }  
 
    public function receipt_page($order) { 
        //echo '<p>' . __('Thank you for your order, please click the button below to pay with Latitude.', 'latitude-gateway') . '</p>';
        $this->_helper->log("receipt_page called!");
        echo $this->generate_checkout_form($order);
        
	}

    public function generate_checkout_form($order_id) {
        $this->_helper->log("ready to generate form");
        global $woocommerce;
        $order = new WC_Order( $order_id );  
        try {
            $payment_request = $this->_payment_request->create_request($order, $this->merchant_secret);
            $form_str = $this->_payment_request->generate_form_request($payment_request);
            return $form_str;
        } catch (Exception $ex) { 
            $this->_helper->log(print_r($ex,true));
        }
    }
 
    public function payment_fields() {    
        // TODO: add field validations here  
        $this->_checkout_banner->load_payment_fields();    
       
   } 

    // default process payment 
    public function process_payment( $order_id ) { 
        global $woocommerce;
        $order = new WC_Order( $order_id );

        do_action( 'woocommerce_receipt_latitude', $order_id );
        
        $this->_helper->log(__("process_payment called: " . $order->get_payment_method())); 
        
        // Mark as on-hold (we're awaiting the cheque)
        // $order->update_status('on-hold', __( 'Awaiting payment', 'woocommerce' ));
        
        // Remove cart
        $woocommerce->cart->empty_cart();
    
        // Return thankyou redirect
        return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        );
    }    

    protected function dbg($msg="")
    {
        ob_start();
        debug_print_backtrace(0,1);
        $_ = ob_get_clean();
        list($ignore,$line_number) = explode(':', $_);
        $line_number += 0;
    
        $backtrace = debug_backtrace(0);
        extract($backtrace[1]);
        echo "<pre>$class::$function($msg) : $line_number</pre>";
    }

	public function get_plugin_name() {
		return $this->plugin_name;
    }
    
	public function get_plugin_version() {
		return $this->plugin_version;
    }    
    

 
}