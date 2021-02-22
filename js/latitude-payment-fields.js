jQuery( function($){

  function validate_pay_method() {
      var PAYMENT_PLUGIN_ID = 'latitude'
      var current_payment = $('input[id=payment_method_' + PAYMENT_PLUGIN_ID + ']:checked', '#payment.woocommerce-checkout-payment').val();    
      console.log('validate_pay_method: current_payment: ' + current_payment ); 
      if (current_payment == PAYMENT_PLUGIN_ID ) {  
        $('button#place_order').hide();
      } else {  
        $('button#place_order').show();
      } 
  }

  $(document).ready(function(){ 
    $( 'form.checkout' ).on( 'change', 'input[name^="payment_method"]', function() { 
      validate_pay_method();
    }); 
  });
});

  
 