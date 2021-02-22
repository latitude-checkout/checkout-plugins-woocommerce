<?php


class Constants
{
    const PLATFORM_TYPE = "woocommerce";
    const PAYMENT_PLUGIN_ID = "latchkout"; 

    const ALLOWED_CURRENCY = array("AUD", "NZD");
    
    const CALLBACK_ROUTE = '/V1/latitude/callback';
    const CANCEL_ROUTE = 'checkout/cart';
    const COMPLETE_ROUTE = 'latitude/payment/complete';

    const ACTIVE = 'active';
    const MERCHANT_ID = 'merchant_id';
    const MERCHANT_SECRET = 'merchant_secret';
    const TEST_MODE = 'test_mode';
    const VERSION = "version";
    const ADVANCED_CONFIG = "advanced_config";
  
    const API_URL_TEST = 'https://api.dev.latitudefinancial.com/v1/applybuy-checkout-service'; 
    const API_URL_PROD = 'https://api.test.latitudefinancial.com/v1/applybuy-checkout-service';
    // const API_URL_PROD = 'https://api.latitudefinancial.com/v1/applybuy-checkout-service';
  
    const CONTENT_HOST_TEST = 'https://master.dev.merchant-services-np.lfscnp.com';
    //const CONTENT_HOST_TEST = 'https://master.test.merchant-services-np.lfscnp.com';
    const CONTENT_HOST_PROD = 'https://checkout.latitudefinancial.com';   
    


}