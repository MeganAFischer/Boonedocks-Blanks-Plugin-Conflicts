<?php
/**
 * Breadcrumb builder for pages.
 *
 * @since   3.5.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Modules\Advanced\Breadcrumbs\Builders;

use SmartCrawl\Modules\Advanced\Breadcrumbs\Helper;

/**
 * Pages breadcrumb class.
 */
class Pages extends Builder {

	/**
	 * Build items for breadcrumb.
	 *
	 * If the page has a parent add it the list.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	protected function prepare_items() {
		global $post;

		$this->reset_items();

		// If current page has a parent.
		if ( ! empty( $post->post_parent ) ) {
			// Set page ancestors.
			$this->set_ancestor_crumbs( $post->ID, 'page', 'post_type' );
		}

		// Current page data.
		if ( ! is_front_page() && ! Helper::get_option( 'hide_post_title' ) ) {
			$this->add_item(
				array( 'title' => $this->get_label( 'page', get_the_title() ) )
			);
		}
	}
}