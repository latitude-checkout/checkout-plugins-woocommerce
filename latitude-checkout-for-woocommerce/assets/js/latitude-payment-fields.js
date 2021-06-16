jQuery(function ($) {
  $(document).ready(function () {
    $("form.checkout").on(
      "change",
      'input[name^="payment_method"]',
      function () {
        var PAYMENT_GATEWAY_ID = "latitudecheckout";
        var current_payment = $(
          "input[id=payment_method_" + PAYMENT_GATEWAY_ID + "]:checked",
          "#payment.woocommerce-checkout-payment"
        ).val();
        if (current_payment == PAYMENT_GATEWAY_ID) {
          $("button#place_order").text("Choose a plan");
        } else {
          $("button#place_order").text("Place order");
        }
        $("button#place_order").show();
      }
    );
    $("form.checkout").change(function () {
      setTimeout(function () {
        if (!$("#latitude-payment--main img").length) {
          window.LatitudeCheckout.renderCheckoutContent();
        } 
      }, 1500); 
    });
  });
});
