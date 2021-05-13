<?php
 
class Environment_Settings
{ 
    const CALLBACK_URL = '/wc-api/latitude_checkout';
 
    const ALLOWED_CURRENCY = ['AUD', 'NZD'];
    const location_settings = array(
        "AUD" => array
            (
                "icon_url" => "https://assets.latitudefinancial.com/merchant-services/latitude/icon/latitude-interest-free.svg", 
                "gateway_title" => "Latitude Interest Fee",
            ),
        "NZD" => array
            (
                "icon_url" => "https://assets.latitudefinancial.com/merchant-services/latitude/icon/gem-interest-free.svg", 
                "gateway_title" => "GEM Interest Fee",
            )
    );

    const api_settings = array (
        "test" => array
            ( 
                "checkout_spa_url" => "https://develop.checkout.test.merchant-services-np.lfscnp.com",
                "checkout_service_url" => "https://api.test.latitudefinancial.com/v1/applybuy-checkout-service"
            ),
        "prod" => array
            ( 
                "checkout_spa_url" => "https://checkout.latitudefinancial.com",
                "checkout_service_url" => "https://api.latitudefinancial.com/v1/applybuy-checkout-service"
            )
    );
  
} 