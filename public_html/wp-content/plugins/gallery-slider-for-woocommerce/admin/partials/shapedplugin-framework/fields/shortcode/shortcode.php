<?php
/**
 * Framework shortcode field file.
 *
 * @link       https://shapedplugin.com/
 *
 * @package    Location_Weather
 * @subpackage Location_Weather/Includes/Admin
 * @author     ShapedPlugin <support@shapedplugin.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
} // Cannot access directly.

if ( ! class_exists( 'WCGS_Field_shortcode' ) ) {
	/**
	 * SP_EAP_Field_shortcode
	 */
	class WCGS_Field_shortcode extends WCGS_Fields {

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
		 * Render
		 *
		 * @return void
		 */
		public function render() {

			// Get the Post ID.
			$post_id = get_the_ID();
			if ( ! empty( $this->field['shortcode'] ) && 'pro_notice' === $this->field['shortcode'] ) {
				if ( ! empty( $post_id ) ) {
					echo '<div class="wcgs_shortcode-area wcgs-pro-notice-wrapper">';
					echo '<div class="wcgs-pro-notice-heading">' . sprintf(
						/* translators: 1: start span tag, 2: close tag. */
						esc_html__( 'Unlock Full Potential with %1$sPRO%2$s', 'gallery-slider-for-woocommerce' ),
						'<span>',
						'</span>'
					) . '</div>';

					echo '<p class="wcgs-pro-notice-desc">' . sprintf(
						/* translators: 1: start bold tag, 2: close tag. */
						esc_html__( 'Create Engaging, Branded Product Galleries with %1$sPro%2$s and Drive More Sales!', 'gallery-slider-for-woocommerce' ),
						'<b>',
						'</b>'
					) . '</p>';

					echo '<ul>';
					echo '<li><i class="sp_wgs-icon-check-icon"></i> ' . esc_html__( '16+ Modern Gallery Layouts', 'gallery-slider-for-woocommerce' ) . '</li>';

					echo '<li><i class="sp_wgs-icon-check-icon"></i> ' . esc_html__( 'Different Layouts Per Category', 'gallery-slider-for-woocommerce' ) . '</li>';
					echo '<li><i class="sp_wgs-icon-check-icon"></i> ' . esc_html__( 'Additional Variation Gallery', 'gallery-slider-for-woocommerce' ) . '</li>';
					echo '<li><i class="sp_wgs-icon-check-icon"></i> ' . esc_html__( 'Product Videos in Galleries', 'gallery-slider-for-woocommerce' ) . '</li>';
					echo '<li><i class="sp_wgs-icon-check-icon"></i> ' . esc_html__( 'Show Product Videos on Shop Page', 'gallery-slider-for-woocommerce' ) . '</li>';
					echo '<li><i class="sp_wgs-icon-check-icon"></i> ' . esc_html__( 'Advanced Product Image Zoom', 'gallery-slider-for-woocommerce' ) . '</li>';
					echo '<li><i class="sp_wgs-icon-check-icon"></i> ' . esc_html__( 'Powerful Product Lightbox', 'gallery-slider-for-woocommerce' ) . '</li>';
					echo '<li><i class="sp_wgs-icon-check-icon"></i> ' . esc_html__( '200+ Customizations and More', 'gallery-slider-for-woocommerce' ) . '</li>';
					echo '</ul>';

					echo '<div class="wcgs-pro-notice-button">';
					echo '<a class="wcgs-open-live-demo" href="https://woogallery.io/pricing/?ref=143" target="_blank">';
					echo esc_html__( 'Upgrade to Pro Now', 'gallery-slider-for-woocommerce' ) . ' <i class="wcgs-icon-shuttle_2285485-1"></i>';
					echo '</a>';
					echo '</div>';
					echo '</div>';
				}
			} else {
				echo ( ! empty( $post_id ) ) ? '
				<div class="wcgs_shortcode-area">
					<p>
						' .
							sprintf(
								/* translators: 1: start strong tag, 2: close tag. */
								esc_html__( 'WooGallery works smoothly with Gutenberg, Classic Editor, %1$sElementor%2$s, Divi, Bricks, Beaver, Oxygen, WPBakery Builder, etc.', 'gallery-slider-for-woocommerce' ),
								'<strong>',
								'</strong>'
							)
						. '
					</p>
				</div>
				' : '';
			}
		}
	}
}
