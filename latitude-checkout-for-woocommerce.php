<?php
/**
 * Plugin Name: Latitude Interest Free Gateway for WooCommerce
 * Plugin URI: https://www.latitudefinancial.com.au/
 * Description: Enabling Latitude Interest Free Payment Gateway on a WooCommerce store. 
 * Author: Latitude Financial Services 
 * Version:1.0.6
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
 
/**
 * Required minimums and constants
 */
define('WC_LATITUDE_GATEWAY__MINIMUM_WP_VERSION', '4.0');
define('WC_LATITUDE_GATEWAY__PLUGIN_VERSION', '1.0.7'); 
define('WC_LATITUDE_GATEWAY__PLUGIN_DIR', plugin_dir_path(__FILE__));

if (!class_exists('WC_Latitude_Checkout_Plugin')) {

    /**
     * Class WC_Latitude_Checkout_Plugin
     */
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
         */
        public static function load_classes()
        {
            if (! class_exists('WC_Payment_Gateway')) {
                return;
            }
 
            include_once WC_LATITUDE_GATEWAY__PLUGIN_DIR . 'includes/environment-settings.php';
            include_once WC_LATITUDE_GATEWAY__PLUGIN_DIR . 'includes/constants.php';
            require_once WC_LATITUDE_GATEWAY__PLUGIN_DIR . 'classes/services/service-api.php';
            require_once WC_LATITUDE_GATEWAY__PLUGIN_DIR . 'classes/services/checkout/purchase.php';
            require_once WC_LATITUDE_GATEWAY__PLUGIN_DIR . 'classes/services/checkout/refund.php';
            require_once WC_LATITUDE_GATEWAY__PLUGIN_DIR . 'classes/services/checkout/verify-purchase.php';
            require_once WC_LATITUDE_GATEWAY__PLUGIN_DIR . 'classes/models/purchase-request.php';
            require_once WC_LATITUDE_GATEWAY__PLUGIN_DIR . 'classes/models/refund-request.php';
            require_once WC_LATITUDE_GATEWAY__PLUGIN_DIR . 'classes/api-callback-handler.php';
            require_once WC_LATITUDE_GATEWAY__PLUGIN_DIR . 'classes/api-handler.php';
            require_once WC_LATITUDE_GATEWAY__PLUGIN_DIR . 'classes/wc-latitudecheckout-gateway.php';
        }

        /**
         * Initialise the plugin class and return an instance.
         *
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
         * Private clone method to prevent cloning of the instance of the
         * *Singleton* instance.
         *
         */
        private function __clone()
        {
            wc_doing_it_wrong(__FUNCTION__, __('Nope'), '1.0');
        }

        /**
         * Private unserialize method to prevent unserializing of the *Singleton*
         * instance.
         *
         */
        public function __wakeup()
        {
            wc_doing_it_wrong(__FUNCTION__, __('Nope'), '1.0');
        }

        /**
         * Plugin constructor.
         *
         * Instantiates the payment gateway and set plugin hooks.
         *
         *
         */
        protected function __construct()
        {
            $gateway = WC_Latitude_Checkout_Gateway::get_instance();
 
            add_filter('woocommerce_payment_gateways', [$this, 'add_gateways'], 10, 1);
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_settings_links'], 10, 1);
        }
 
        /**
         * Callback for when this plugin is activated.
         *
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
         * Note: Hooked onto the "plugin_action_links_latitude-checkout-for-woocommerce.php" Action.
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
