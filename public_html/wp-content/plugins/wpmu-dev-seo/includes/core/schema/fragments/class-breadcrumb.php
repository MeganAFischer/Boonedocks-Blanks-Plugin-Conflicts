<?php
/**
 * Breadcrumb schema fragment class.
 *
 * @since   3.5.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Fragments;

use SmartCrawl\Modules\Advanced\Breadcrumbs\Controller;
use SmartCrawl\Schema\Utils;

/**
 * Schema builder for breadcrumb.
 */
class Breadcrumb extends Fragment {

	/**
	 * Utility class.
	 *
	 * @var Utils $utils
	 */
	private $utils;

	/**
	 * Initialize the class.
	 *
	 * @since 3.5.0
	 */
	public function __construct() {
		$this->utils = Utils::get();
	}

	/**
	 * Get the raw schema data for breadcrumb.
	 *
	 * @since 3.5.0
	 *
	 * @return mixed|void
	 */
	protected function get_raw() {
		$crumbs = Controller::get()->get_current_builder()->get_items();
		// No crumbs. Do nothing.
		if ( empty( $crumbs ) ) {
			return false;
		}

		$counter = 1;
		$count   = count( $crumbs );

		$schema = array(
			'@type'           => 'BreadcrumbList',
			'@id'             => $this->utils->url_to_id( $this->utils->get_current_url(), '#breadcrumb' ),
			'itemListElement' => array(),
		);

		foreach ( $crumbs as $crumb ) {
			if ( ! empty( $crumb['title'] ) ) {
				$list_element = array(
					'@type'    => 'ListItem',
					'position' => $counter,
					'name'     => $crumb['title'],
				);

				if ( $counter < $count && ! empty( $crumb['link'] ) ) {
					$list_element['item'] = $crumb['link'];
				}

				$schema['itemListElement'][] = $list_element;

				++$counter;
			}
		}

		return $schema;
	}
}