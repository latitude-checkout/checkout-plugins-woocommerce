<?php
/**
 * Latitude Checkout API Callback Class
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
 

/**
 * Latitude_Checkout_API_Callbacks class.
 *
 * Class that handles API callbacks.
 */
 
class Latitude_Checkout_API_Callbacks
{ 
  	/**
	 * The reference the *Singleton* instance of this class.
	 *
	 * @var $instance
	 */
	private static $instance;

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return Latitude_Checkout_API_Callbacks The *Singleton* instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

    /**
	 * Latitude_Checkout_API_Callbacks constructor.
	 */
	public function __construct() {
 
	}

}
Latitude_Checkout_API_Callbacks::get_instance();