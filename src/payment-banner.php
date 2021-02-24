<?php

use Constants as LatitudeConstants; 
use Latitude_Gateway_Helper as LatitudeHelper;

class Latitude_Payment_Banner
{
    protected $_helper;

    public function __construct(
        LatitudeHelper $helper
    ) {
        $this->_helper = $helper;   
    }  

    public function get_override() {
        return json_encode("");
    }

    public function get_script_url() {
        //return $this->_helper->get_script_url();
        return "https://master.checkout.dev.merchant-services-np.lfscnp.com/assets/content.js?platform=magento2&merchantId=aulmerchantuser";
    }

    public function get_options() {
        return json_encode([ 
            "merchantId" => $this->_helper->get_merchant_id(),
            "currency" => $this->_helper->get_base_currency(),
            "container" =>  "",
            "page" => "checkout",            
            "container" => [
                "footer" => "latitude-payment--footer",
                "main" =>  "latitude-payment--main", 
            ] 
        ]);         
    } 

  
 

    public function load_payment_fields() { 


        ?> 
        <script language="Javascript" type="text/javascript">   
            var target_url = '<?php echo $this->get_script_url(); ?>';
            window.LatitudeCheckout = <?php echo $this->get_options(); ?>;    
            (function () {
                function asyncLoad() {
                
                    var curr = document.createElement("script");
                    curr.type = "text/javascript";
                    curr.async = true;
                    curr.src = '<?php echo $this->get_script_url(); ?>';
                    var scr = document.getElementsByTagName("script")[0];
                    scr.parentNode.insertBefore(curr, scr);   
                } 

                if (window.attachEvent) {
                    window.attachEvent("onload", asyncLoad);
                } else {
                    window.addEventListener("load", asyncLoad, false);
                }

            })();      
        </script> 
        <div id="latitude-payment--main">
        <div style="display: flex !important; justify-content: center !important">
        <svg
            version="1.1"
            style="height: 50px !important"
            id="L4"
            xmlns="http://www.w3.org/2000/svg"
            xmlns:xlink="http://www.w3.org/1999/xlink"
            x="0px"
            y="0px"
            viewBox="0 0 100 100"
            enable-background="new 0 0 0 0"
            xml:space="preserve"
        >
            <circle fill="#0046AA" stroke="none" cx="6" cy="50" r="6">
            <animate
                attributeName="opacity"
                dur="2s"
                values="0;1;0"
                repeatCount="indefinite"
                begin="0.1"
            />
            </circle>
            <circle fill="#0046AA" stroke="none" cx="26" cy="50" r="6">
            <animate
                attributeName="opacity"
                dur="2s"
                values="0;1;0"
                repeatCount="indefinite"
                begin="0.2"
            />
            </circle>
            <circle fill="#0046AA" stroke="none" cx="46" cy="50" r="6">
            <animate
                attributeName="opacity"
                dur="2s"
                values="0;1;0"
                repeatCount="indefinite"
                begin="0.3"
            />
            </circle>
        </svg>
        </div>
        <p style="margin-top: 14px !important; margin-bottom: 14px !important">
            You will be redirected to Latitude checkout to complete your order
        </p>
        </div>  
        <div id="latitude-payment--footer"></div> 
        <?php 
        
    }    
}
 