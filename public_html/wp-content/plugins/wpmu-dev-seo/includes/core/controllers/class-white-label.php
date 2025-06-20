<?php
/**
 * White label functionality.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Controllers;

use SmartCrawl\Singleton;

/**
 * Class White_Label
 */
class White_Label extends Controller {

	use Singleton;

	/**
	 * Bind listening actions
	 *
	 * @return bool
	 */
	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'add_inline_styling' ), 15 );

		return true;
	}

	/**
	 * Add inline styles for white label.
	 *
	 * @return void
	 */
	public function add_inline_styling() {
		$new_image = $this->get_wpmudev_hero_image( '' );
		if ( $this->is_hide_wpmudev_branding() && $new_image ) {
			wp_add_inline_style(
				Assets::APP_CSS,
				".wrap-wds .sui-rebranded .sui-summary-image-space {
					background-image: url('{$new_image}') !important;
				}"
			);
		}

		if ( $this->is_hide_wpmudev_doc_link() ) {
			wp_add_inline_style(
				Assets::APP_CSS,
				'.wrap-wds .sui-header .sui-actions-right .wds-docs-button, .wrap-wds .learn-more {
					display: none;
				}
				'
			);
		}
	}

	/**
	 * Get white labelled branding image.
	 *
	 * @param string $hero_image Default image.
	 *
	 * @return string
	 */
	public function get_wpmudev_hero_image( $hero_image ) {
		return apply_filters( 'wpmudev_branding_hero_image', $hero_image );
	}

	/**
	 * Is branding hidden?.
	 *
	 * @return bool
	 */
	public function is_hide_wpmudev_branding() {
		return apply_filters( 'wpmudev_branding_hide_branding', false );
	}

	/**
	 * Is white label doc links enabled.
	 *
	 * @return bool
	 */
	public function is_hide_wpmudev_doc_link() {
		return apply_filters( 'wpmudev_branding_hide_doc_link', false );
	}

	/**
	 * Get white labelled footer text.
	 *
	 * @param string $footer_text Footer text.
	 *
	 * @return string
	 */
	public function get_wpmudev_footer_text( $footer_text ) {
		return apply_filters( 'wpmudev_branding_footer_text', $footer_text );
	}

	/**
	 * Get class for summary box.
	 *
	 * @return string
	 */
	public function summary_class() {
		if ( ! $this->is_hide_wpmudev_branding() ) {
			return '';
		}

		return $this->get_wpmudev_hero_image( '' ) ? 'sui-rebranded' : 'sui-unbranded';
	}
}