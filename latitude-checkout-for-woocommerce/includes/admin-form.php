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
        'description' => __('Enable the payment gateway', 'woo_latitudecheckout'), 
        'default'     => 'yes',
    ), 
    'test_mode' => array( 
        'title'       => __('Test Mode', 'woo_latitudecheckout'),
        'label'       => __('Is Test Mode?', 'woo_latitudecheckout'),
        'type'        => 'checkbox',
        'description' => __('Place the payment gateway in test mode', 'woo_latitudecheckout'), 
        'default'     => 'no',  
    ),
    'advanced_config' => array(
        'title'       => __('Advanced Config', 'woo_latitudecheckout'),
        'type'        => 'textarea',
        'description' => __('Please refer to Integration guide or contact Relationship Manager for further clarification. More information at <a href="https://checkout.latitudefinancial.com/playground/widget/">Widget Playground</a>.', 'woo_latitudecheckout'),
        'default'     =>    '{"productWidget": {
                                    "layout": "inversed",
                                    "paymentFrequency": "monthly",
                                    "promotionMonths": 13
                                }}',  
    ));