<?php
/**
 * This file contains the WooCommerce checkout page customization.
 *
 * @package wdm-lionheart-customization
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Enums\OrderStatus;

if ( ! class_exists( 'Wdm_Wc_Checkout' ) ) {
	/**
	 *
	 * This class contains the WooCommerce checkout page customization.
	 */
	class Wdm_Wc_Checkout {

		/**
		 * Instance of the class
		 *
		 * @var mixed $instance
		 */
		private static $instance;

		/**
		 * Manual Payment options
		 *
		 * @var array $manual_payment_options
		 */
		private $manual_payment_options;

		/**
		 * Constructor for initializing the class with filter hook to override payment gateways for invoice customers.
		 *
		 * @return void
		 */
		public function __construct() {
			$manual_payment_options       = get_option( 'wdm_lionheart_settings' );
			$this->manual_payment_options = ! empty( $manual_payment_options ) ? $manual_payment_options : array();
			add_filter( 'woocommerce_available_payment_gateways', array( $this, 'wdm_override_payment_gateways' ) );
			add_filter( 'woocommerce_cod_process_payment_order_status', array( $this, 'wdm_set_cod_order_status'), 100, 2 );
		}

		/**
		 * Overrides the available payment gateways based on the user's roles.
		 *
		 * @param  array $gateways The available payment gateways.
		 * @return array The modified list of available payment gateways.
		 */
		public function wdm_override_payment_gateways( $gateways ) {
			$current_user     = wp_get_current_user();
			$user_roles       = $current_user->roles;
			$saved_user_roles = isset( $this->manual_payment_options['wdm_user_roles_for_manual_payment'] ) ? $this->manual_payment_options['wdm_user_roles_for_manual_payment'] : array();
			if ( empty( array_intersect( $saved_user_roles, $user_roles ) ) ) {
				unset( $gateways['cod'] );
			}
			return $gateways;
		}

		/**
		 * Override the by-default on-hold or processing status for COD orders to Pending Payment.
		 *
		 * @param  string $status Current order status.
		 * @param  WC_Order $order Order object.
		 */
		public function wdm_set_cod_order_status( $status, $order ) {
			$payment_method = $order->get_payment_method();
			if( 'cod' === $payment_method ) {
				return OrderStatus::PENDING;
			}
			return $status;
		}

		/**
		 * Retrieve the singleton instance of the class.
		 *
		 * Ensures only one instance of the class is loaded or can be loaded.
		 *
		 * @return Wdm_Wc_Checkout The singleton instance of the class.
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}
	}
}

Wdm_Wc_Checkout::get_instance();
