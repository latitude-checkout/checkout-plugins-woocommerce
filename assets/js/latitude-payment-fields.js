jQuery( function($){ 
  $(document).ready(function(){ 
    $( 'form.checkout' ).on( 'change', 'input[name^="payment_method"]', function() { 
          var PAYMENT_GATEWAY_ID =  'latitudecheckout';
          var current_payment = $('input[id=payment_method_' + PAYMENT_GATEWAY_ID + ']:checked', '#payment.woocommerce-checkout-payment').val();    
          console.log('validate_pay_method: current_method: ' + current_payment + ", plugin_method: " + PAYMENT_GATEWAY_ID);  
          if (current_payment == PAYMENT_GATEWAY_ID ) {  
            $('button#place_order').text('Choose a plan'); 
          } else {  
            $('button#place_order').text('Place order');
          } 
          $('button#place_order').show(); 
    }); 
  });
});

  