<?php

class Environment_Settings
{ 
    const CALLBACK_URL = '/wc-api/latitude_checkout';
 
    const ALLOWED_CURRENCY = ['AUD', 'NZD'];
    const location_settings = array(
        "AUD" => array
            (
                "icon_url" => "https://assets.latitudefinancial.com/merchant-services/latitude/icon/latitude-interest-free.svg",
                "icon_alt_text" => "Latitude Interest Fee",
            ),
        "NZD" => array
            (
                "icon_url" => "https://assets.latitudefinancial.com/merchant-services/latitude/icon/gem-interest-free.svg",
                "icon_alt_text" => "GEM Interest Fee",
            )
    );

    const api_settings = array (
        "test" => array
            (
                "payment_fields_url" => "https://master.checkout.dev.merchant-services-np.lfscnp.com",
                "widgets_url" => "https://checkout.latitudefinancial.com",
                "checkout_service_url" => "https://api.dev.latitudefinancial.com/v1/applybuy-checkout-service"
            ),
        "prod" => array
            (
                "payment_fields_url" => "https://master.checkout.dev.merchant-services-np.lfscnp.com", 
                "widgets_url" => "https://checkout.latitudefinancial.com",
                "checkout_service_url" => "https://api.test.latitudefinancial.com/v1/applybuy-checkout-service"
            )
    );
  
}