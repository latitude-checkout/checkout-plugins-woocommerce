<?php
/**
 * Plugin Name: Latitude Interest Free Gateway for WooCommerce
 * Plugin URI: https://www.latitudefinancial.com.au/
 * Description: Enabling Latitude Interest Free Payment Gateway on a WooCommerce store.
 * Author: Latitude Financial Services 
 * Version:1.0.4
 * Text Domain: latitude-checkout-for-woocommerce
 * 
 * WC requires at least: 3.2.0
 * WC tested up to: 5.2.2
 *
 * Copyright (C) 2021  Latitude Checkout
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 */

if (!defined('ABSPATH')) {
    exit();
}

define('WC_LATITUDE_GATEWAY__MINIMUM_WP_VERSION', '4.0'); 
define('WC_LATITUDE_GATEWAY__PLUGIN_VERSION', '1.0.4');   
define('WC_LATITUDE_GATEWAY__PLUGIN_DIR', plugin_dir_path(__FILE__));

if (!class_exists('WC_Latitude_Checkout_Plugin')) {
    class WC_Latitude_Checkout_Plugin
    {
        /**
         *
         * The plugin instance.
         *
         * @var		WC_Latitude_Checkout_Plugin		$instance	A static reference to an instance of this class.
         */
        protected static $instance;

        /**
         * Import required classes.
         *
         * @since    1.0.0
         * @access   public
         */
        public static function load_classes()
        { 
            if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
				return;
			}
 
            include_once WC_LATITUDE_GATEWAY__PLUGIN_DIR . 'includes/environment-settings.php'; 
            require_once WC_LATITUDE_GATEWAY__PLUGIN_DIR . 'classes/services/service-api.php';
            require_once WC_LATITUDE_GATEWAY__PLUGIN_DIR . 'classes/services/checkout/purchase.php';
            require_once WC_LATITUDE_GATEWAY__PLUGIN_DIR . 'classes/services/checkout/verify-purchase.php';            
            require_once WC_LATITUDE_GATEWAY__PLUGIN_DIR . 'classes/models/purchase-request.php';
            require_once WC_LATITUDE_GATEWAY__PLUGIN_DIR . 'classes/api-callback-handler.php';            
            require_once WC_LATITUDE_GATEWAY__PLUGIN_DIR . 'classes/api-handler.php'; 
            require_once WC_LATITUDE_GATEWAY__PLUGIN_DIR . 'classes/wc-latitudecheckout-gateway.php';  
        }

        /**
         * Plugin constructor.
         *
         * Instantiates the payment gateway and set plugin hooks.
         *
         * @since    1.0.0
         *
         */
        public function __construct()
        {
            $gateway = WC_Latitude_Checkout_Gateway::get_instance();

            /*
            * Actions 
            */ 
            add_action("woocommerce_update_options_payment_gateways_{$gateway->id}",[$gateway, 'process_admin_options'],10,0 ); 
            add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
            add_action("woocommerce_receipt_{$gateway->id}",[$gateway, 'receipt_page'],10,1); 
            add_action('woocommerce_admin_order_data_after_order_details', [$gateway,'display_order_data_in_admin',]);
            add_action('woocommerce_before_checkout_form',[$gateway, 'add_checkout_custom_style'],10,2); 
            add_action('woocommerce_single_product_summary',[$gateway, 'get_widget_data'],10,2);
            add_action( 'woocommerce_after_checkout_validation', [ $gateway, 'validate_checkout_fields'], 10, 2 );  
            /*
            * Filters
            */
            add_filter( 'woocommerce_payment_gateways', [$this, 'add_gateways'], 10, 1 );
            add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_settings_links'], 10, 1 ); 
            add_filter( 'woocommerce_gateway_icon', [$gateway, 'filter_gateway_icon'], 10, 2 );
            add_filter( 'woocommerce_order_button_text', [$gateway, 'filter_place_order_button_text'], 10, 1 );
            add_filter( 'woocommerce_endpoint_order-pay_title', [$gateway, 'filter_order_pay_title'], 10, 2 );  
 
        }

        /**
         * Note: Hooked onto the "wp_enqueue_scripts" Action
         *
         *  @since    1.0.0
         */
        public function enqueue_scripts()
        {
            /**
             * Enqueue JS for updating  place order button text on payment method change
             */

            wp_enqueue_script(
                'latitude_payment_fields_js',  
                plugin_dir_url( __FILE__) . 'assets/js/latitude-payment-fields.js', 
                ['jquery']
            ); 
             
        }

        /**
         * Initialise the class and return an instance.
         *
         * @since    1.0.0
         */
        public static function init()
        {
            self::load_classes();
            if (!class_exists('WC_Latitude_Checkout_Gateway')) {
                return false;
            }
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Callback for when this plugin is activated.
         *
         * @since    1.0.0
         */
        public static function activate_plugin()
        {
            if (!current_user_can('activate_plugins')) {
                return;
            }
            self::init();
        }

        /**
         * Callback for when this plugin is deactivated.
         *
         * @since    1.0.0
         */
        public static function deactivate_plugin()
        {
            if (!current_user_can('activate_plugins')) {
                return;
            }
            self::load_classes();
        }

        /**
         * Callback for when the plugin is uninstalled. Remove all of its data. 
         *  
         */
        public static function uninstall_plugin()
        {
            if (!current_user_can('activate_plugins')) {
                return;
            }
            return;
        }
 

        /**
         * Adds the Latitude Checkout Payments Gateway to WooCommerce
         *
         */

        public function add_gateways($gateways)
        {
            $gateways[] = 'WC_Latitude_Checkout_Gateway';
            return $gateways;
        }


        /**
         * Note: Hooked onto the "plugin_action_links_checkout-plugins-woocommerce.php" Action.
         *  
         *
         */
        public function add_settings_links($links)
        {
            $settings_links = [
                '<a href="' .
                admin_url(
                    'admin.php?page=wc-settings&tab=checkout&section=latitudecheckout'
                ) .
                '">' .
                __('Settings', 'woo_latitudecheckout') .
                '</a>',
            ];

            return array_merge($settings_links, $links);
        }
    }
 
    register_activation_hook(__FILE__, [ 'WC_Latitude_Checkout_Plugin', 'activate_plugin', ]);
    register_deactivation_hook(__FILE__, [ 'WC_Latitude_Checkout_Plugin', 'deactivate_plugin', ]);
    register_uninstall_hook(__FILE__, [ 'WC_Latitude_Checkout_Plugin', 'uninstall_plugin', ]); 
    add_action('plugins_loaded', ['WC_Latitude_Checkout_Plugin', 'init'], 10, 0);
}
