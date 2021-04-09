<?php

class Constants
{
    // plugin information
    const WC_LATITUDE_GATEWAY_PLATFORM = 'woocommerce';
    const WC_LATITUDE_GATEWAY_ID = 'latitudecheckout';
    const WC_LATITUDE_GATEWAY_VERSION = '0.0.1';
    const WC_LATITUDE_GATEWAY_NAME = 'Latitude Interest Free';

    const AU_ICON_URL = 'https://assets.latitudefinancial.com/merchant-services/latitude/icon/latitude-interest-free.svg';
    const NZ_ICON_URL = 'https://assets.latitudefinancial.com/merchant-services/latitude/icon/gem-interest-free.svg';

    const ALLOWED_CURRENCY = ['AUD', 'NZD'];

    const CANCEL_ROUTE = 'checkout/cart';
    const COMPLETE_ROUTE = '/wc-api/latitude_checkout';

    const PAYMENT_FIELDS_URL_PROD = 'https://master.checkout.dev.merchant-services-np.lfscnp.com';
    const PAYMENT_FIELDS_URL_TEST = 'https://master.checkout.dev.merchant-services-np.lfscnp.com';

    const WIDGETS_URL_PROD = 'https://checkout.latitudefinancial.com'; // to confirm if this url is the same for both test and prod env
    const WIDGETS_URL_TEST = 'https://checkout.latitudefinancial.com';
}
