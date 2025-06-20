<?php
/**
 * Framework license field.
 *
 * @package    Woo_Gallery_Slider_Pro
 * @subpackage Woo_Gallery_Slider_Pro/public
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
} // Cannot access directly.

if ( ! class_exists( 'WCGS_Field_license' ) ) {
	/**
	 *
	 * Field: license
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	class WCGS_Field_license extends WCGS_Fields {

		/**
		 * Field constructor.
		 *
		 * @param array  $field The field type.
		 * @param string $value The values of the field.
		 * @param string $unique The unique ID for the field.
		 * @param string $where To where show the output CSS.
		 * @param string $parent The parent args.
		 */
		public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {

			parent::__construct( $field, $value, $unique, $where, $parent );
		}

		/**
		 * Render field
		 *
		 * @return void
		 */
		public function render() {

			echo wp_kses_post( $this->field_before() );
			$type = ( ! empty( $this->field['attributes']['type'] ) ) ? $this->field['attributes']['type'] : 'text';
			echo '<div class="woo-gallery-slider-pro-license text-center">';
			echo '<h3>' . esc_html__( 'You\'re using WooGallery Lite - No License Needed. Enjoy! 🙂', 'gallery-slider-for-woocommerce' ) . '</h3>';
			echo '<p>To get access to more premium features, consider <a href="https://woogallery.io/pricing/?ref=143" target="_blank">Upgrading To Pro!</a></p>';
			echo '<div class="woo-gallery-slider-pro-license-area">';
			echo '<div class="woo-gallery-slider-pro-license-key"><input class="woo-gallery-slider-pro-license-key-input" disabled type="' . esc_attr( $type ) . '" name="' . esc_attr( $this->field_name() ) . '" value="' . esc_attr( $this->value ) . '" /></div>';
			echo '<input type="button" class="button-secondary read-only btn-license-save-activate" name="sp_woo_gallery_slider_pro_license_activated" value="' . esc_html__( 'Activate', 'gallery-slider-for-woocommerce' ) . '"/>';
			echo '</div>';
			echo '</div>';
			echo wp_kses_post( $this->field_after() );
		}

	}
}
