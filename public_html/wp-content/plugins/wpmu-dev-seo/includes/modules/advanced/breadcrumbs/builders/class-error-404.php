<?php
/**
 * Breadcrumb builder for 404.
 *
 * @since   3.5.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Modules\Advanced\Breadcrumbs\Builders;

/**
 * 404 breadcrumb class.
 */
class Error_404 extends Builder {

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
		$this->add_item(
			array(
				'title' => $this->get_label(
					'404',
					__( '404 Error: Page not found', 'wds' )
				),
			)
		);
	}
}