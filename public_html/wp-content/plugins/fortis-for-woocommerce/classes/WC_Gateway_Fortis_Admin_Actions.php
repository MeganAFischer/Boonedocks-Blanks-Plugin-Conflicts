<?php
/**
 * Admin Actions for WC_Payment_Gateway
 *
 * @package Fortis for WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Fortis Payment Gateway - Fortispay3
 *
 * Supports admin actions
 *
 * @class       woocommerce_fortis
 * @package     WooCommerce
 */
class WC_Gateway_Fortis_Admin_Actions extends WC_Gateway_Fortis {

	/**
	 * Add a notice to the merchant_key and merchant_id fields when in test mode.
	 *
	 * @param mixed $form_fields Form fields.
	 *
	 * @since 1.0.0
	 */
	public static function add_testmode_admin_settings_notice( $form_fields ) {
		return $form_fields;
	}

	/**
	 * Custom order action - query order status
	 * Add custom action to order actions select box
	 *
	 * @param mixed $actions Actions.
	 */
	public static function fortis_add_order_meta_box_action( $actions ) {
		return $actions;
	}
}
