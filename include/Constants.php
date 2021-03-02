<?php


class Constants
{
    // plugin information
    const WC_LATITUDE_GATEWAY_PLATFORM = "woocommerce";
    const WC_LATITUDE_GATEWAY_ID = "latitudecheckout"; 
    const WC_LATITUDE_GATEWAY_VERSION = "0.0.1";
    const WC_LATITUDE_GATEWAY_NAME = "Latitude"; 


    const AU_ICON_URL = "https://assets.latitudefinancial.com/merchant-services/latitude/icon/latitude-interest-free.svg";
    const NZ_ICON_URL = "https://assets.latitudefinancial.com/merchant-services/latitude/icon/gem-interest-free.svg";

    const ALLOWED_CURRENCY = array("AUD", "NZD");
    
    const CALLBACK_ROUTE = '/wp-json/latitude/v1/callback';
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

}