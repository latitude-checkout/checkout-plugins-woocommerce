<?php
/**
 * Plugin Name: Latitude Payment Gateway for WooCommerce
 * Description: Integrates Latitude Payments into your Woocommerce store.
 * Author: Latitude Financial
 * Author URI: https://www.latitudefinancial.com.au/
 * Version: 0.0.1
 * Text Domain: checkout-plugins-woocommerce
 * WC tested up to: 5.6
 *
 */

if (!defined('ABSPATH')) {
	exit;
}
 
  // Make sure no info is exposed if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'This plugin is not intended to be called directly.';
	exit;
}
 
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );

 // Make sure woocommerce is active 
 if (! in_array ('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option( 'active_plugins')))) {
     return; 
 } 

define( 'WC_LATITUDE_GATEWAY_VERSION', '0.0.1' );
define( 'WC_LATITUDE_GATEWAY__MINIMUM_WP_VERSION', '4.0' );
define( 'WC_LATITUDE_GATEWAY__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

add_action( 'plugins_loaded', 'wc_latitude_gateway_init');
function wc_latitude_gateway_init() {
    if (class_exists('WC_Payment_Gateway')) { 
        $files = glob(WC_LATITUDE_GATEWAY__PLUGIN_DIR . 'src/*.php', GLOB_BRACE);
        foreach($files as $file) {
            require_once($file);            
        }
        $wc_plugin = new WC_Latitude_Gateway();     
    }   
} 

// Add the Latitude Checkout Payment Gateway to WC Available Gateways
add_filter( 'woocommerce_payment_gateways', 'latitude_add_gateway_class' );
function latitude_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_Latitude_Gateway';  
	return $gateways;
}
 