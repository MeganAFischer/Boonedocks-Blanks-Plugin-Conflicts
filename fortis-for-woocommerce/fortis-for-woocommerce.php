<?php
/**
 * Plugin Name: Fortis for WooCommerce
 * Description: Receive payments using the Fortis payments provider.
 * Author: Fortis Payment Systems
 * Author URI: https://fortispay.com/
 * Version: 1.1.2
 * Requires at least: 6.0
 * Tested up to: 6.8.0
 *
 * Woo: 18734003307187:5af1fe7212d9675f3bea8d98af3207eb
 * WC tested up to: 9.8.1
 * WC requires at least: 7.0
 *
 * @package Fortis for WooCommerce
 *
 * Developer: App Inlet (Pty) Ltd
 * Developer URI: https://www.appinlet.com/
 *
 * Copyright: 2025 Fortis Payment Systems, LLC (“Fortis”)
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: fortis-for-woocommerce
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use JetBrains\PhpStorm\NoReturn;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action( 'plugins_loaded', 'fortis_init', 0 );

const WC_GATEWAY_FORTIS = 'classes/WC_Gateway_Fortis.php';

/**
 * Initialize the gateway.
 *
 * @since 1.0.0
 * @noinspection PhpUnused
 */
function fortis_init() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	require_once plugin_basename( WC_GATEWAY_FORTIS );

	add_filter( 'woocommerce_payment_gateways', 'fortis_add_gateway' );

	/**
	 * Custom order action - query order status
	 * Add custom action to order actions select box
	 */
	add_action(
		'woocommerce_order_actions',
		array( WC_Gateway_Fortis_Admin_Actions::class, 'fortis_add_order_meta_box_action' )
	);

	add_action( 'woocommerce_before_cart', array( WC_Gateway_Fortis::class, 'show_cart_messages' ), 10, 1 );
} // End fortis_init()

add_filter( 'woocommerce_available_payment_gateways', 'fortis_payment_gateway_disable_private' );
add_filter( 'woocommerce_order_needs_payment', array( WC_Gateway_Fortis::class, 'order_needs_payment' ), 10, 3 );

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'fortis_settings_link' );

/**
 * Makes json request
 *
 * @return void
 */
function fortis_ajax_request() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/blocks/class-wc-fortis-payments-blocks.php';

	$wc_blocks = new WC_Gateway_Fortis_Blocks_Support();
	$wc_blocks->initialize();

	echo wp_json_encode( $wc_blocks->get_payment_method_data() );
	die();
}

add_action( 'wp_ajax_fortis_ajax_request', 'fortis_ajax_request' );
add_action( 'wp_ajax_nopriv_fortis_ajax_request', 'fortis_ajax_request' );

/**
 * Generate iframe form
 *
 * @return void
 */
function fortis_get_billing_data() {
	$fortis_gateway = new WC_Gateway_Fortis();

	$data = $fortis_gateway->getBillingData();

	echo wp_json_encode( $data );

	wp_die();
}

add_action( 'wp_ajax_get_billing_data', 'fortis_get_billing_data' );
add_action( 'wp_ajax_nopriv_get_billing_data', 'fortis_get_billing_data' );
add_action(
	'woocommerce_admin_order_data_after_payment_info',
	array(
		WC_Gateway_Fortis::class,
		'add_ach_refund_message',
	),
	10,
	1
);

/**
 * Links
 *
 * @param array $links Links.
 *
 * @return array
 */
function fortis_settings_link( array $links ) {
	$plugin_links = array(
		'<a href="admin.php?page=wc-settings&tab=checkout&section=fortis">' . esc_html__(
			'Settings',
			'fortis-for-woocommerce'
		) . '</a>',
	);

	return array_merge( $plugin_links, $links );
}

/**
 * Add the gateway to WooCommerce
 *
 * @param array $methods Methods.
 *
 * @return array
 * @since 1.0.0
 */
function fortis_add_gateway( array $methods ) {
	require_once WC_GATEWAY_FORTIS;

	$methods[] = 'WC_Gateway_Fortis';

	return $methods;
} // End fortis_add_gateway().

/**
 * Disable the gateway
 *
 * @param array $available_gateways Gateways available.
 *
 * @return array
 */
function fortis_payment_gateway_disable_private( array $available_gateways ) {
	require_once WC_GATEWAY_FORTIS;

	$fortis_api = new FortisApi( 'Fortis' );

	if ( '' === $fortis_api->framework->get_user_id() ||
		'' === $fortis_api->framework->get_user_api_key() ||
		'yes' !== $fortis_api->framework->get_enabled() ) {
		unset( $available_gateways['fortis'] );
	}

	return $available_gateways;
}

/**
 * Registers WooCommerce Blocks integration.
 */
class WC_Fortis_Payments {


	/**
	 * Plugin bootstrapping.
	 */
	public static function init() {
		// Registers WooCommerce Blocks integration.
		add_action(
			'woocommerce_blocks_loaded',
			array( __CLASS__, 'fortis_gateway_fortis_woocommerce_block_support' )
		);
	}

	/**
	 * Add the Fortis Payment gateway to the list of available gateways.
	 *
	 * @param array $gateways Gateways.
	 */
	public static function add_gateway( array $gateways ) {
		$gateways[] = 'WC_Gateway_Fortis';

		return $gateways;
	}

	/**
	 * Plugin includes.
	 */
	public static function includes() {
		// Make the WC_Gateway_Fortis class available.
		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			require_once 'classes/WC_Gateway_Fortis.php';
		}
	}

	/**
	 * Plugin url.
	 *
	 * @return string
	 */
	public static function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Plugin url.
	 *
	 * @return string
	 */
	public static function plugin_abspath() {
		return trailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Registers WooCommerce Blocks integration.
	 */
	public static function fortis_gateway_fortis_woocommerce_block_support() {
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'includes/blocks/class-wc-fortis-payments-blocks.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new WC_Gateway_Fortis_Blocks_Support() );
				}
			);
		}
	}
}

/**
 * Declares support for HPOS.
 *
 * @return void
 */
function fortis_declare_hpos_compatibility() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			__FILE__,
			true
		);
	}
}

add_action( 'before_woocommerce_init', 'fortis_declare_hpos_compatibility' );

// Add "Complete Auth" button on the order detail screen
add_action( 'woocommerce_order_item_add_action_buttons', 'add_complete_auth_button_to_order_details' );
function add_complete_auth_button_to_order_details( $order ) {
	// Display the button only if the order has 'auth-only' meta
	if ( 'auth-only' === $order->get_meta( 'action' ) && 'on-hold' == $order->status ) {
		echo '<button type="button" class="button complete-auth-button" data-order-id="' . esc_attr( $order->get_id() ) . '">' . __(
			'Complete Auth',
			'fortis'
		) . '</button>';
	}
}

// Add "Complete Auth" option to the Order Actions dropdown if the order has 'auth-only' meta
add_filter( 'woocommerce_order_actions', 'conditionally_add_complete_auth_order_action' );
function conditionally_add_complete_auth_order_action( $actions ) {
	global $theorder;

	// Check if we're on the WooCommerce Order Edit screen and have a valid order
	if ( $theorder && 'auth-only' === $theorder->get_meta( 'action' ) && 'on-hold' == $theorder->status ) {
		$actions['complete_auth'] = __( 'Complete Auth', 'fortis' );
	}

	return $actions;
}

// Handle the "Complete Auth" action from the Order Actions dropdown
add_action( 'woocommerce_order_action_complete_auth', 'process_complete_auth_order_action' );
function process_complete_auth_order_action( $order ) {
	$result = complete_auth_for_order( $order->get_id() );

	// Display a notice in the admin area
	if ( $result['success'] ) {
		WC_Admin_Notices::add_custom_notice( 'complete_auth_success', $result['message'] );
	} else {
		WC_Admin_Notices::add_custom_notice( 'complete_auth_error', $result['message'] );

		// Redirect back to the order edit page without the default "Order updated" notice
		wp_safe_redirect(
			add_query_arg(
				array(
					'post'   => $order->get_id(),
					'action' => 'edit',
				),
				admin_url( 'post.php' )
			)
		);
		exit;
	}
}

// Add Complete Auth Column
add_filter( 'manage_woocommerce_page_wc-orders_columns', 'add_wc_order_list_custom_column' );
function add_wc_order_list_custom_column( $columns ) {
	$reordered_columns = array();

	// Inserting columns to a specific location
	foreach ( $columns as $key => $column ) {
		$reordered_columns[ $key ] = $column;

		if ( 'order_status' === $key ) {
			// Inserting after "Status" column
			$reordered_columns['complete_auth'] = __( '', 'fortis' );
		}
	}

	return $reordered_columns;
}

// Display the 'Complete Auth' button on order list
add_action( 'manage_woocommerce_page_wc-orders_custom_column', 'display_wc_order_list_custom_column_content', 10, 2 );
function display_wc_order_list_custom_column_content( $column, $order ) {
	// Display the 'Complete Auth' button only if the order is not already complete
	if ( $column === 'complete_auth' && 'auth-only' === $order->get_meta( 'action' ) && 'on-hold' == $order->status ) {
		echo '<button class="button complete-auth-button" data-order-id="' . esc_attr( $order->ID ) . '">' . __(
			'Complete Auth',
			'fortis'
		) . '</button>';
	}
}

// Enqueue JavaScript to handle button click
add_action( 'admin_enqueue_scripts', 'enqueue_complete_auth_js' );
function enqueue_complete_auth_js() {
	wp_enqueue_script(
		'complete-auth-js',
		plugin_dir_url( __FILE__ ) . 'assets-non-blocks/js/complete-auth.js',
		array( 'jquery' ),
		null,
		true
	);

	// Pass the `ajaxurl` and nonce to the script
	wp_localize_script(
		'complete-auth-js',
		'completeauth',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'complete_auth_nonce' ),
		)
	);
}

// Handle the Ajax request to process Complete Auth
add_action( 'wp_ajax_complete_auth_action', 'complete_auth_action' );
function complete_auth_action() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'complete_auth_nonce' ) ) {
		wp_send_json_error( array( 'message' => 'Nonce verification failed.' ) );
	}

	if ( ! current_user_can( 'edit_shop_orders' ) ) {
		wp_send_json_error( array( 'message' => 'You are not allowed to complete orders.' ) );
	}

	$order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
	if ( $order_id > 0 ) {
		$result = complete_auth_for_order( $order_id );
		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}
	} else {
		wp_send_json_error( array( 'message' => 'Invalid order ID.' ) );
	}
}

// Reusable function to complete auth for an order
function complete_auth_for_order( $order_id ) {
	$order = wc_get_order( $order_id );

	if ( $order && 'auth-only' === $order->get_meta( 'action' ) ) {
		$fortis_api         = new FortisApi( 'Fortis' );
		$transaction_amount = (int) ( $order->get_total() * 100 );
		$transaction_id     = $order->get_meta( 'transaction_id', true );
		$status             = $fortis_api->completeAuth( $transaction_id, $transaction_amount, $order_id );

		if ( $status == 1 ) {
			$order->update_meta_data( 'action', 'sale' );
			$order->update_status( 'processing' );
			$order->save();

			return array(
				'success' => true,
				'message' => __( 'Order completed successfully via Complete Auth.', 'fortis' ),
			);
		} else {
			return array(
				'success' => false,
				'message' => __( 'Order could not be completed via Complete Auth.', 'fortis' ),
			);
		}
	} else {
		return array(
			'success' => false,
			'message' => __( 'Invalid or non-auth-only order selected.', 'fortis' ),
		);
	}
}


WC_Fortis_Payments::init();
