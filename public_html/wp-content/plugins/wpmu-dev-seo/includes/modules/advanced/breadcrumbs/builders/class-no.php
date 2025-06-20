<?php
/**
 * Breadcrumb builder when breadcrumb is not required.
 *
 * @since   3.5.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Modules\Advanced\Breadcrumbs\Builders;

use SmartCrawl\Singleton;

/**
 * No breadcrumb class.
 */
class No {

	use Singleton;

	/**
	 * Get empty crumbs list.
	 *
	 * @since 3.5.0
	 *
	 * @return array
	 */
	public function get_items() {
		/**
		 * Filter to modify the breadcrumb items.
		 *
		 * @since 3.5.0
		 *
		 * @param array $items Prepared items.
		 */
		return apply_filters( 'smartcrawl_breadcrumbs_get_empty_items', array() );
	}

	/**
	 * Render empty breadcrumb.
	 *
	 * @since 3.5.0
	 *
	 * @param string $before What to show before the breadcrumb.
	 * @param string $after  What to show after the breadcrumb.
	 *
	 * @return string
	 */
	public function render( $before = '', $after = '' ) {
		$output = sprintf(
			'%s%s',
			empty( $before ) ? '' : wp_kses_post( $before ),
			empty( $after ) ? '' : wp_kses_post( $after )
		);

		add_filter(
			'smartcrawl_breadcrumbs_render_empty_output',
			function ( $output ) {
				if ( is_front_page() ) {
					$output = '<span>Home</span>';
				}

				return $output;
			}
		);

		/**
		 * Filter to modify final breadcrumb html output.
		 *
		 * @since 3.5.0
		 *
		 * @param string $output Generated html.
		 */
		return apply_filters( 'smartcrawl_breadcrumbs_render_empty_output', $output );
	}

	/**
	 * Prepare nothing.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	public function maybe_prepare_items() {
		/**
		 * Action hook to run after breadcrumb items are prepared.
		 *
		 * @since 3.5.0
		 *
		 * @param mixed $items Items prepared.
		 */
		do_action( 'smartcrawl_breadcrumbs_after_prepare_empty_items', array() );
	}
}