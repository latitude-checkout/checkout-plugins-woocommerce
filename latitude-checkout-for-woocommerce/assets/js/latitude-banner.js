window.LatitudeCheckout = {
  merchantId: latitude_banner_js_vars.merchantId,
  page: "checkout",
  container: latitude_banner_js_vars.container,
  containerClass: "",
  currency: latitude_banner_js_vars.currency,
  layout: "",
  paymentFrequency: "",
  promotionMonths: "",
  product: {
    id: latitude_banner_js_vars.id,
    name: latitude_banner_js_vars.name,
    category: latitude_banner_js_vars.category,
    price: latitude_banner_js_vars.price,
    sku: latitude_banner_js_vars.sku,
  },
};

window.LatitudeCheckoutOverride = "";

(function () {
  function asyncLoad() {
    var curr = document.createElement("script");
    curr.type = "text/javascript";
    curr.async = true;
    curr.src = latitude_banner_js_vars.assetUrl;

    var scr = document.getElementsByTagName("script")[0];
    scr.parentNode.insertBefore(curr, scr);
  }

  if (window.attachEvent) {
    window.attachEvent("onload", asyncLoad);
  } else {
    window.addEventListener("load", asyncLoad, false);
  }
})();
