jQuery( function($){

  function validate_pay_method() {
      var PAYMENT_PLUGIN_ID =  'latchkout';
      var current_payment = $('input[id=payment_method_' + PAYMENT_PLUGIN_ID + ']:checked', '#payment.woocommerce-checkout-payment').val();    
      console.log('validate_pay_method: current_method: ' + current_payment + ", plugin_method: " + PAYMENT_PLUGIN_ID);  
      if (current_payment == PAYMENT_PLUGIN_ID ) {  
        $('button#place_order').text = 'Choose a Plan';
      } else {  
        $('button#place_order').text = 'Place order';
      } 
  }

  $(document).ready(function(){ 
    $( 'form.checkout' ).on( 'change', 'input[name^="payment_method"]', function() { 
      validate_pay_method();
    }); 
  });
});

  
 