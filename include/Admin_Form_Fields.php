<?php
/**
* Latitude Checkout Plugin Admin Form Fields
*/


$this->form_fields =  array(           
    'merchant_id' => array(
        'title'       => _('Merchant ID', 'woo_latitudecheckout'),
        'type'        => 'text',
        'description' => __('Merchant ID provided by Latitude', 'woo_latitudecheckout'),
        'desc_tip'    => false,
        'default'     => 'MerchantID',
    ),
    'merchant_secret' => array(
        'title'       => _('Merchant Secret Key', 'woo_latitudecheckout'),
        'type'        => 'password',
        'description' => __('Merchant Secret Key provided by Latitude', 'woo_latitudecheckout'),
        'desc_tip'    => false,
        'default'     =>  'MerchantSecretKey',
    ),                
    'enabled' => array(
        'title'       => __('Enable/Disable', 'woo_latitudecheckout'),
        'label'       => __('Is Enabled?', 'woo_latitudecheckout'),
        'type'        => 'checkbox',
        'description' => __('Enable Latitude Checkout Payment Gateway', 'woo_latitudecheckout'),
        'desc_tip'    => false,
        'default'     => 'yes',
    ),
    'testmode' => array(
        'title'       => __('Test Mode', 'woo_latitudecheckout'),
        'label'       => __('Is Test Mode?', 'woo_latitudecheckout'),
        'type'        => 'checkbox',
        'description' => __('Place the payment gateway in test mode', 'woo_latitudecheckout'),
        'default'     => 'yes',
        'desc_tip'    => false,
    ),
    'widget_content' => array(
        'title'       => __('Widget', 'woo_latitudecheckout'),
        'type'        => 'textarea',
        'description' => __('Copy values from <a href="https://develop.checkout.test.merchant-services-np.lfscnp.com/playground/widget/">Widget Playground</a>', 'woo_latitudecheckout'),
        'desc_tip'    => false,
    ));