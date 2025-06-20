<?php
/**
 * Manages WooCommerce Global Identifier.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Modules\Advanced\WooCommerce;

use SmartCrawl\Controllers;
use SmartCrawl\Integration\WooCommerce\Data;
use SmartCrawl\Singleton;

/**
 * Global Identifier controller
 */
class Global_Id extends Controllers\Controller {

	use Singleton;

	const GLOBAL_ID_META_KEY = '_wds_global_id';

	const GLOBAL_ID_VARIATION_NAME = '_wds_global_id_variable';

	/**
	 * Should this module run?.
	 *
	 * @return bool
	 */
	public function should_run() {
		return ! empty( $this->get_global_id() );
	}

	/**
	 * Initialization method.
	 *
	 * @return void
	 */
	protected function init() {
		add_action( 'woocommerce_product_options_sku', array( $this, 'add_global_id_field' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_global_id' ) );
		add_filter( 'woocommerce_structured_data_product', array( $this, 'add_global_id_to_woocommerce_schema' ), 15, 2 );

		/* phpcs:disable
		// We don't support variable global IDs yet because we don't have anywhere to put them ATM
		add_action( 'woocommerce_product_after_variable_attributes', array(
			$this,
			'add_variation_global_id_field',
		), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation_global_id' ), 10, 2 );
		*/
		// phpcs:enable
	}

	/**
	 * Adds global ID field to WooCommerce product options.
	 *
	 * @return void
	 */
	public function add_global_id_field() {
		$global_id = $this->get_global_id();
		$label     = $this->get_global_id_label( $global_id );

		?>
		<div class="options_group">
			<?php
			woocommerce_wp_text_input(
				array(
					'id'          => self::GLOBAL_ID_META_KEY,
					'label'       => $label,
					'desc_tip'    => true,
					'description' => sprintf(
						/* translators: 1: Meta key label, 2: plugin title */
						esc_html__( '%1$s value to use in the %2$s Product schema.', 'wds' ),
						$label,
						\smartcrawl_get_plugin_title()
					),
				)
			);
			?>
		</div>
		<?php
	}

	/**
	 * Saves GTIN code.
	 *
	 * @param \WC_Product $product Product Object.
	 */
	public function save_global_id( $product ) {
		if ( ! isset( $_POST[ self::GLOBAL_ID_META_KEY ] ) ) { // phpcs:ignore
			return;
		}

		$product->update_meta_data(
			self::GLOBAL_ID_META_KEY,
			\smartcrawl_clean( wp_unslash( $_POST[ self::GLOBAL_ID_META_KEY ] ) ) // phpcs:ignore -- sanitized before use.
		);
	}

	/**
	 * Adds global ID field to WooCommerce product variation.
	 *
	 * @param int     $loop           Position in the loop.
	 * @param array   $variation_data Variation data.
	 * @param WP_Post $variation      Post data.
	 *
	 * @return void
	 */
	public function add_variation_global_id_field( $loop, $variation_data, $variation ) {
		$global_id        = $this->get_global_id();
		$label            = $this->get_global_id_label( $global_id );
		$variation_object = wc_get_product( $variation->ID );
		$value            = $variation_object->get_meta( self::GLOBAL_ID_META_KEY );

		woocommerce_wp_text_input(
			array(
				'id'            => self::GLOBAL_ID_VARIATION_NAME . "[{$loop}]",
				'name'          => self::GLOBAL_ID_VARIATION_NAME . "[{$loop}]",
				'value'         => $value,
				'label'         => $label,
				'desc_tip'      => true,
				'description'   => sprintf(
					/* translators: 1: Variation name, 2: plugin title */
					esc_html__( '%1$s value to use in $2$s Product schema.', 'wds' ),
					$label,
					\smartcrawl_get_plugin_title()
				),
				'wrapper_class' => 'form-row',
			)
		);
	}

	/**
	 * Saves global ID for product variation.
	 *
	 * @param int $variation_id WC_Product_Variation object.
	 * @param int $index Index within loop.
	 *
	 * @return void
	 */
	public function save_variation_global_id( $variation_id, $index ) {
		if ( ! isset( $_POST[ self::GLOBAL_ID_VARIATION_NAME ] ) ) { // phpcs:ignore
			return;
		}

		$global_id = wp_unslash( $_POST[ self::GLOBAL_ID_VARIATION_NAME ][ $index ] ); // phpcs:ignore -- sanitized before use.
		$variation = wc_get_product( $variation_id );
		$variation->update_meta_data( self::GLOBAL_ID_META_KEY, \smartcrawl_clean( $global_id ) );
		$variation->save_meta_data();
	}

	/**
	 * Retrieves global ID label.
	 *
	 * @param string $global_id Global ID.
	 *
	 * @return string
	 */
	private function get_global_id_label( $global_id ) {
		if ( 'isbn' === $global_id ) {
			return esc_html__( 'ISBN', 'wds' );
		}

		if ( 'mpn' === $global_id ) {
			return esc_html__( 'MPN', 'wds' );
		}

		return esc_html__( 'GTIN', 'wds' );
	}

	/**
	 * Retrieves global ID value.
	 *
	 * @return string
	 */
	private function get_global_id() {
		return \smartcrawl_get_array_value( $this->options, 'global_id' );
	}

	/**
	 * Adds global ID to WooCommerce schema.
	 *
	 * @param array       $markup  Schema markup.
	 * @param \WC_Product $product Produce.
	 *
	 * @return mixed
	 */
	public function add_global_id_to_woocommerce_schema( $markup, $product ) {
		if ( empty( $markup ) ) {
			// We may have removed the schema.
			return $markup;
		}

		$global_id_key = $this->get_global_id();
		if ( ! empty( $markup[ $global_id_key ] ) ) {
			// Global identifier already set.
			return $markup;
		}

		$global_id_value = $product->get_meta( self::GLOBAL_ID_META_KEY );
		if ( $global_id_value ) {
			$markup[ $global_id_key ] = \smartcrawl_clean( $global_id_value );
		}

		return $markup;
	}
}