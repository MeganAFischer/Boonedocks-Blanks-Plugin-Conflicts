<?php
/**
 * Plugin Name: WooCommerce UPS Shipping
 * Plugin URI: https://woocommerce.com/products/ups-shipping-method/
 * Description: WooCommerce UPS Shipping allows a store to obtain shipping rates for your orders dynamically via the UPS Shipping API.
 * Version: 3.8.4
 * Author: WooCommerce
 * Author URI: https://woocommerce.com
 * Text Domain: woocommerce-shipping-ups
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * Requires PHP: 7.4
 * Requires at least: 6.7
 * Tested up to: 6.8
 * WC requires at least: 9.7
 * WC tested up to: 9.9
 *
 * Copyright: © 2025 WooCommerce
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Woo: 18665:8dae58502913bac0fbcdcaba515ea998
 *
 * @package WC_Shipping_UPS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WC_SHIPPING_UPS_VERSION', '3.8.4' ); // WRCS: DEFINED_VERSION.
define( 'WC_SHIPPING_UPS_PLUGIN_DIR', __DIR__ );
define( 'WC_SHIPPING_UPS_PLUGIN_URL', untrailingslashit( plugins_url( '', __FILE__ ) ) );
define( 'WC_SHIPPING_UPS_DIST_DIR', WC_SHIPPING_UPS_PLUGIN_DIR . '/dist/' );
define( 'WC_SHIPPING_UPS_DIST_URL', WC_SHIPPING_UPS_PLUGIN_URL . '/dist/' );

/**
 * Plugin activation check
 */
function wc_ups_activation_check() {
	if ( ! function_exists( 'simplexml_load_string' ) ) {
		deactivate_plugins( basename( __FILE__ ) );
		wp_die( "Sorry, but you can't run this plugin, it requires the SimpleXML library installed on your server/hosting to function." );
	}
}

register_activation_hook( __FILE__, 'wc_ups_activation_check' );

add_action( 'plugins_loaded', 'wc_shipping_ups_init' );

// Subscribe to automated translations.
add_filter( 'woocommerce_translations_updates_for_' . basename( __FILE__, '.php' ), '__return_true' );

/**
 * Initialize plugin.
 */
function wc_shipping_ups_init() {
	require_once 'vendor/autoload_packages.php';
	require_once WC_SHIPPING_UPS_PLUGIN_DIR . '/includes/class-wc-shipping-ups-init.php';

	WC_Shipping_UPS_Init::get_instance();
}
