<?php

/**
 * Latitude Checkout Plugin Environment settings
 *
 */


class Latitude_Checkout_Environment_Settings
{
    const CALLBACK_URL = '/wc-api/latitude_checkout';
    const DEFAULT_CURRENCY = 'AUD';
    const ALLOWED_CURRENCY = ['AUD', 'NZD'];
    const location_settings = array( 
        "AUD" => array(
            "icon_url" => "https://assets.latitudefinancial.com/merchant-services/latitude/icon/latitude-interest-free.svg",
            "gateway_title" => "Latitude Interest Free",
        ),
        "NZD" => array(
            "icon_url" => "https://assets.latitudefinancial.com/merchant-services/latitude/icon/gem-interest-free.svg",
            "gateway_title" => "GEM Interest Free",
        )
    );

    const api_settings = array(
        "test" => array(
            "content_url" => "https://develop.checkout.test.merchant-services-np.lfscnp.com",
            "checkout_service_url" => "https://api.test.latitudefinancial.com/v1/applybuy-checkout-service"
        ),
        "prod" => array(
            "content_url" => "https://checkout.latitudefinancial.com",
            "checkout_service_url" => "https://api.latitudefinancial.com/v1/applybuy-checkout-service"
        ) 
    );

    public static function get_icon_url()
    {
        $currency = self::get_base_currency();
        return self::location_settings[$currency]["icon_url"];
    }

    public static function get_gateway_title()
    {
        $currency = self::get_base_currency();
        return self::location_settings[$currency]["gateway_title"];
    }

    public static function get_content_url($is_test_mode)
    {
        if ($is_test_mode) {
            return self::api_settings["test"]["content_url"];
        }
        return self::api_settings["prod"]["content_url"];
    }

    public static function get_service_url($is_test_mode)
    {
        if ($is_test_mode) {
            return self::api_settings["test"]["checkout_service_url"];
        }
        return self::api_settings["prod"]["checkout_service_url"];
    }

    public static function get_base_currency()
    {
        $currency = get_woocommerce_currency();
        if (!in_array($currency, self::ALLOWED_CURRENCY)) {
            return self::DEFAULT_CURRENCY;
        }
        return $currency;
    }
}
