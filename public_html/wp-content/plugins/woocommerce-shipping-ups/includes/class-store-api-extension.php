<?php
/**
 * Store_API_Extension class.
 *
 * A class to extend the store public API with UPS shipping functionality.
 *
 * @package WC_Shipping_UPS
 */

namespace WooCommerce\UPS;

use Automattic\WooCommerce\StoreApi\StoreApi;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\Utilities\CartController;
use WC_Shipping_Zones;

/**
 * Store API Extension.
 */
class Store_API_Extension {
	/**
	 * Stores Rest Extending instance.
	 *
	 * @var ExtendSchema
	 */
	private static $extend;

	/**
	 * Plugin Identifier, unique to each plugin.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'wc_shipping_ups';

	/**
	 * Bootstraps the class and hooks required data.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		self::$extend = StoreApi::container()->get( ExtendSchema::class );
		self::extend_store();
	}

	/**
	 * Registers the data into each endpoint.
	 */
	public static function extend_store() {

		self::$extend->register_endpoint_data(
			array(
				'endpoint'        => CartSchema::IDENTIFIER,
				'namespace'       => self::IDENTIFIER,
				'data_callback'   => array( static::class, 'data' ),
				'schema_callback' => array( static::class, 'schema' ),
				'schema_type'     => ARRAY_A,
			)
		);

		self::$extend->register_update_callback(
			array(
				'namespace' => self::IDENTIFIER,
				'callback'  => array( static::class, 'update' ),
			)
		);
	}

	/**
	 * Store API extension data callback.
	 *
	 * @return array
	 */
	public static function data() {
		$cart_controller = new CartController();
		$packages        = $cart_controller->get_shipping_packages();
		$has_ups         = false;

		foreach ( $packages as $package ) {
			$shipping_zone    = WC_Shipping_Zones::get_zone_matching_package( $package );
			$shipping_methods = $shipping_zone->get_shipping_methods( true );

			if ( empty( $shipping_methods ) ) {
				continue;
			}

			$ups_methods = array_filter(
				$shipping_methods,
				function ( $shipping_method ) {
					return 'ups' === $shipping_method->id;
				}
			);

			if ( ! empty( $ups_methods ) ) {
				$has_ups = true;
			}
		}

		if ( false === $has_ups ) {
			return array(
				'debug_notices'   => array(),
				'success_notices' => array(),
				'error_notices'   => array(),
			);
		}

		$notices = Notifier::get_notices();

		Notifier::clear_notices();

		$html_formatter = self::$extend->get_formatter( 'html' );

		foreach ( $notices as $type => $type_notices ) {
			foreach ( $type_notices as $index => $notice ) {
				// PHPStan cannot correctly find Automattic\WooCommerce\StoreApi\Formatters\FormatterInterface
				// @phpstan-ignore class.notFound
				$notices[ $type ][ $index ]['message'] = $html_formatter->format( $notice['message'] );
			}
		}

		return array(
			'debug_notices'   => ! empty( $notices['notice'] ) ? $notices['notice'] : array(),
			'success_notices' => ! empty( $notices['success'] ) ? $notices['success'] : array(),
			'error_notices'   => ! empty( $notices['error'] ) ? $notices['error'] : array(),
		);
	}

	/**
	 * Store API extension schema callback.
	 *
	 * @return array Registered schema.
	 */
	public static function schema() {
		return array(
			'debug_notices'   => array(
				'description' => __( 'UPS debug notices', 'woocommerce-shipping-ups' ),
				'type'        => array( 'array' ),
				'context'     => array( 'view' ),
				'readonly'    => true,
			),
			'success_notices' => array(
				'description' => __( 'UPS success notices', 'woocommerce-shipping-ups' ),
				'type'        => array( 'array' ),
				'context'     => array( 'view' ),
				'readonly'    => true,
			),
			'error_notices'   => array(
				'description' => __( 'UPS error notices', 'woocommerce-shipping-ups' ),
				'type'        => array( 'array' ),
				'context'     => array( 'view' ),
				'readonly'    => true,
			),
		);
	}

	/**
	 * Store API extension update callback.
	 *
	 * @param array $data Data to update.
	 */
	public static function update( $data ) {

		if ( ! isset( $data['action'] ) || 'apply_suggested_shipping_address' !== $data['action'] ) {
			return;
		}

		// Get the cart customer.
		$customer = WC()->cart->get_customer();

		// Get the suggested address.
		$suggested_address = ! empty( $data['suggested_address'] )
			? json_decode( $data['suggested_address'], true )
			: false;

		// Return if no suggested address.
		if ( ! $suggested_address ) {
			return;
		}

		// Sanitize the address.
		$address_keys = array( 'address_1', 'address_2', 'city', 'state', 'postcode', 'country' );
		$address      = array();
		foreach ( $address_keys as $key ) {
			$address[ $key ] = isset( $suggested_address[ $key ] ) ? sanitize_text_field( $suggested_address[ $key ] ) : '';
		}

		// Set the cart customer shipping address.
		$customer->set_shipping_address_1( $address['address_1'] );
		$customer->set_shipping_address_2( $address['address_2'] );
		$customer->set_shipping_city( $address['city'] );
		$customer->set_shipping_state( $address['state'] );
		$customer->set_shipping_postcode( $address['postcode'] );
		$customer->set_shipping_country( $address['country'] );

		// Maybe set the billing address.
		if ( ! empty( $data['use_shipping_as_billing'] ) ) {
			$customer->set_billing_address_1( $address['address_1'] );
			$customer->set_billing_address_2( $address['address_2'] );
			$customer->set_billing_city( $address['city'] );
			$customer->set_billing_state( $address['state'] );
			$customer->set_billing_postcode( $address['postcode'] );
			$customer->set_billing_country( $address['country'] );
		}

		// Save the cart customer.
		$customer->save();
	}
}
