<?php
/**
 * Breadcrumb builder for search results page.
 *
 * @since   3.5.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Modules\Advanced\Breadcrumbs\Builders;

/**
 * Search breadcrumb class.
 */
class Search extends Builder {

	/**
	 * Build items for breadcrumb.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	protected function prepare_items() {
		$this->reset_items();

		// Current page data.
		$this->add_item_with_paged(
			array(
				'link'  => get_search_link(),
				'title' => $this->get_label(
					'search',
					// translators: %s search query.
					sprintf( __( "Search for '%s'", 'wds' ), get_search_query() )
				),
			)
		);
	}
}