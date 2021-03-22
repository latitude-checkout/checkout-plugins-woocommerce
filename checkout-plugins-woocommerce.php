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

define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);

define('WC_LATITUDE_GATEWAY__MINIMUM_WP_VERSION', '4.0');
define('WC_LATITUDE_GATEWAY__PLUGIN_DIR', plugin_dir_path(__FILE__));

if (!class_exists('LatitudeCheckoutPlugin')) {
    class LatitudeCheckoutPlugin
    {
        /**
         * @var		LatitudeCheckoutPlugin		$instance	A static reference to an instance of this class.
         */
        protected static $instance;

        /**
         * Import required classes.
         *
         */
        public static function load_classes()
        {
            if (class_exists('WC_Payment_Gateway')) {
                require_once WC_LATITUDE_GATEWAY__PLUGIN_DIR .
                    'include/Constants.php';
                require_once WC_LATITUDE_GATEWAY__PLUGIN_DIR .
                    'include/Latitude_Purchase_Request.php';
                require_once WC_LATITUDE_GATEWAY__PLUGIN_DIR .
                    'include/Latitude_Checkout_Service.php';
                require_once WC_LATITUDE_GATEWAY__PLUGIN_DIR .
                    'include/WC_LatitudeCheckoutGateway.php';
            }
        }

        /**
         * Class constructor. Called when an object of this class is instantiated.
         *
         */
        public function __construct()
        {
            $gateway = WC_LatitudeCheckoutGateway::get_instance();
            add_action(
                "woocommerce_update_options_payment_gateways_{$gateway->id}",
                [$gateway, 'process_admin_options'],
                10,
                0
            );
            add_filter(
                'woocommerce_payment_gateways',
                [$gateway, 'add_latitudecheckoutgateway'],
                10,
                1
            );

            add_action(
                "woocommerce_update_options_payment_gateways_{$gateway->id}",
                [$gateway, 'refresh_configuration'],
                11,
                0
            );
            add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
            add_action(
                "woocommerce_receipt_{$gateway->id}",
                [$gateway, 'receipt_page'],
                10,
                1
            );
            add_action('woocommerce_api_latitude_checkout', [
                $gateway,
                'on_latitude_checkout_callback',
            ]);
            add_filter(
                'woocommerce_gateway_icon',
                [$gateway, 'filter_latitude_gateway_icon'],
                10,
                2
            );
            add_filter(
                'woocommerce_order_button_text',
                [$gateway, 'filter_place_order_button_text'],
                10,
                1
            );
            add_filter(
                'woocommerce_endpoint_order-pay_title',
                [$gateway, 'filter_order_pay_title'],
                10,
                2
            );
            add_action('woocommerce_admin_order_data_after_order_details', [
                $gateway,
                'display_order_data_in_admin',
            ]);
        }

        /**
         * Note: Hooked onto the "wp_enqueue_scripts" Action
         *
         */
        public function enqueue_scripts()
        {
            /**
             * Enqueue JS for updating  place order button text on payment method change
             */

            wp_enqueue_script(
                'latitude_payment_fields_js',
                plugins_url('js/latitude-payment-fields.js', __FILE__),
                ['jquery']
            );
        }

        /**
         * Initialise the class and return an instance.
         *
         */
        public static function init()
        {
            self::load_classes();
            if (!class_exists('WC_LatitudeCheckoutGateway')) {
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
    }

    register_activation_hook(__FILE__, [
        'LatitudeCheckoutPlugin',
        'activate_plugin',
    ]);
    register_deactivation_hook(__FILE__, [
        'LatitudeCheckoutPlugin',
        'deactivate_plugin',
    ]);
    register_uninstall_hook(__FILE__, [
        'LatitudeCheckoutPlugin',
        'uninstall_plugin',
    ]);
    add_action('plugins_loaded', ['LatitudeCheckoutPlugin', 'init'], 10, 0);
}
