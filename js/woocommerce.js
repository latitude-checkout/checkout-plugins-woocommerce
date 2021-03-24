  window.LatitudeCheckout = {
  merchantId: latitude_widget_js_vars.merchantId,
  page: "product",
  container: "latitude-banner-container",
  containerClass: "",
  currency: latitude_widget_js_vars.currency,
  layout: "",
  paymentFrequency: "",
  promotionMonths: "",
  product: {
    id: latitude_widget_js_vars.id,
    name: latitude_widget_js_vars.name,
    category: latitude_widget_js_vars.category,
    price: latitude_widget_js_vars.price,
    sku: latitude_widget_js_vars.sku,
  },
};

window.LatitudeCheckoutOverride = latitude_widget_js_vars.widgetSettings;

(function () {
  function asyncLoad() {
    var curr = document.createElement("script");
    curr.type = "text/javascript";
    curr.async = true;
    curr.src = latitude_widget_js_vars.assetUrl; 

    var scr = document.getElementsByTagName("script")[0];
    scr.parentNode.insertBefore(curr, scr);
  }

  if (window.attachEvent) {
    window.attachEvent("onload", asyncLoad);
  } else {
    window.addEventListener("load", asyncLoad, false);
  }
})();
