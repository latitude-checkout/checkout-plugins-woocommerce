<?php
/**
* Latitude Checkout Plugin Admin Form Fields
*/ 


$this->form_fields =  array(           
    'merchant_id' => array(
        'title'       => __('Merchant ID', 'woo_latitudecheckout'),
        'type'        => 'text', 
        'default'     => '',
    ),
    'merchant_secret' => array(
        'title'       => __('Merchant Secret Key', 'woo_latitudecheckout'),
        'type'        => 'password', 
        'default'     =>  '',
    ),                
    'enabled' => array(
        'title'       => __('Enable/Disable', 'woo_latitudecheckout'),
        'label'       => __('Is Enabled?', 'woo_latitudecheckout'),
        'type'        => 'checkbox',
        'description' => __('When disabled, payment option would not be available on checkout page.', 'woo_latitudecheckout'), 
        'default'     => 'yes',
    ), 
    'test_mode' => array( 
        'title'       => __('Test Mode', 'woo_latitudecheckout'),
        'label'       => __('Is Test Mode?', 'woo_latitudecheckout'),
        'type'        => 'checkbox',
        'description' => __('When enabled, sends requests to our sandbox environment.', 'woo_latitudecheckout'), 
        'default'     => 'no',  
    ),
    'debug_mode' => array( 
        'title'       => __('Debug Mode', 'woo_latitudecheckout'),
        'label'       => __('Is Debug Mode?', 'woo_latitudecheckout'),
        'type'        => 'checkbox',
        'description' => __('When enabled, adds extra information in logs.', 'woo_latitudecheckout'), 
        'default'     => 'no',  
    ),
    'advanced_config' => array(
        'title'       => __('Advanced Config', 'woo_latitudecheckout'),
        'type'        => 'textarea',
        'description' => __('This field expects a valid JSON. Refer merchant integration guide or contact LFS representative for more details.', 'woo_latitudecheckout'),
        'default'     =>    '{"productWidget": {
                                    "layout": "inversed",
                                    "paymentFrequency": "monthly",
                                    "promotionMonths": 12
                                }}',  
    ));