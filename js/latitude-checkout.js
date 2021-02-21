 
  
function onChoosePlan(target_url) {
    jQuery( function($){ 
        jQuery.noConflict();
        jQuery(document).ready(function() {
            
            alert( "checkout.js here! " + target_url );
            // var data = {
            //     'action': 'latitude_checkout_order_action', 
            // }; 
            // jQuery.post(target_url, data, function(response) {
            //     alert('Got this from the server: ' + response);
            // });

            // var data = {
            //     'action': 'onChoosePlanHook', 
            // }; 
            // jQuery.post("checkout.php", data, function(response) {
            //     alert('Got this from the server: ' + response);
            // });

        });
    });
}

 

//http://localhost:5000/wp-content/plugins/latitude-gateway-for-woocommerce/js/latitude-payment-fields.js?ver=5.6