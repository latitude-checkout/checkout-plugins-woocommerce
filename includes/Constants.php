<?php

class Constants
{
    // plugin information 
    const WC_LATITUDE_GATEWAY_PLATFORM = 'woocommerce';
    const WC_LATITUDE_GATEWAY_ID = 'latitudecheckout'; 
    const WC_LATITUDE_GATEWAY_NAME = 'Latitude Interest Free';

    // icon config
    const AU_ICON_URL = 'https://assets.latitudefinancial.com/merchant-services/latitude/icon/latitude-interest-free.svg';
    const AU_ICON_ALT_TEXT = 'Latitude Interest Fee';
    const NZ_ICON_URL = 'https://assets.latitudefinancial.com/merchant-services/latitude/icon/gem-interest-free.svg';
    CONST NZ_ICON_ALT_TEXT =  'GEM Interest Fee';
    
    const ALLOWED_CURRENCY = ['AUD', 'NZD'];
  
    const CALLBACK_URL = '/wc-api/latitude_checkout';

    const PAYMENT_FIELDS_URL_PROD = 'https://master.checkout.dev.merchant-services-np.lfscnp.com';
    const PAYMENT_FIELDS_URL_TEST = 'https://master.checkout.dev.merchant-services-np.lfscnp.com';

    const WIDGETS_URL_PROD = 'https://checkout.latitudefinancial.com'; // to confirm if this url is the same for both test and prod env
    const WIDGETS_URL_TEST = 'https://checkout.latitudefinancial.com';

    // wc order status
    const WC_ORDER_FAILED = 'failed';
    const WC_ORDER_CANCELLED = 'cancelled';
    const WC_ORDER_PENDING = 'pending';
    const WC_ORDER_ONHOLD = 'on-hold';
}