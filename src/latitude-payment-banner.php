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
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );  

    } 

    public function enqueue_scripts() {    

       // wp_enqueue_script( 'latitude_checkout_js', plugins_url( '../js/latitude-checkout.js', __FILE__ ), array('jquery'), '', true );
        //wp_localize_script( 'latitude_checkout_js', 'latitude_ajax_object', array( 'ajax_url' => 'wc-latitude-gateway.php' ));

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
        <div class="primary">
        <button  
            class="action primary checkout"
            id="latitude-payment-button"
            style="
                width: 100% !important;
                max-width: 400px !important;
                font-size: 18px !important;
                line-height: 1 !important;
                padding: 14px 17px !important;
            " 
            type="submit"     
            onClick="onChoosePlan(target_url)"
            >
            <span> Choose a plan</span>
            </button>     
        </div>   
        <div id="latitude-payment--footer"></div> 
        <?php 
        
    }    
}

    
// jQuery( function($){ 
//     $('button#latitude-payment-button').on('click',function(event){
//         $.ajax(
//             {
//                 type: "post",
//                 url: latitude_ajax_object.ajax_url,
//                 data: {'action': 'latitude_place_order_action'},
//                 success: function(response){
//                     alert(response);
//             }
//         });
//     });
// });
  
 

// <script type="text/javascript">
// jQuery( function($){ 
//     jQuery.noConflict();
//     $(document).ready(function() {
//         $('button#latitude-payment-button').on('click',function(event){
//             alert( "Handler for .click() called." );
//         });
//     });
// });
// </script>