jQuery( function($){

  function validate_pay_method() {
      var LATITUDE_PAYMENT_METHOD = 'latitude-checkout'
      var current_payment = $('input[id=payment_method_' + LATITUDE_PAYMENT_METHOD + ']:checked', '#payment.woocommerce-checkout-payment').val();    
      console.log('validate_pay_method: current_payment: ' + current_payment ); 
      if (current_payment == LATITUDE_PAYMENT_METHOD ) {  
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

  
 