<?php
/**
 * Breadcrumb builder for taxonomies.
 *
 * @since   3.5.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Modules\Advanced\Breadcrumbs\Builders;

/**
 * Taxonomies breadcrumb class.
 */
class Taxonomies extends Builder {

	/**
	 * Taxonomy name.
	 *
	 * @since 3.5.0
	 *
	 * @var string $taxonomy Taxonomy name.
	 */
	protected $taxonomy;

	/**
	 * Build items for breadcrumb.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	protected function prepare_items() {
		// Make sure taxonomy is set.
		if ( empty( $this->taxonomy ) ) {
			$this->set_taxonomy();
		}

		$this->reset_items();

		// Current term.
		$term = get_queried_object();
		if ( ! $term instanceof \WP_Term ) {
			return;
		}

		// Set ancestor crumbs.
		$this->set_ancestor_crumbs( $term->term_id, $this->taxonomy );

		// Set current term to crumb.
		$this->add_item_with_paged(
			array(
				'link'  => get_term_link( $term->term_id ),
				'title' => $this->get_title( $term->name ),
			)
		);
	}

	/**
	 * Set current taxonomy name.
	 *
	 * @since 3.5.0
	 *
	 * @param string $name Taxonomy name.
	 *
	 * @return void
	 */
	public function set_taxonomy( $name = '' ) {
		if ( empty( $name ) ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				$name = $term->taxonomy;
			}
		}

		$this->taxonomy = $name;
	}

	/**
	 * Get the title for archive item.
	 *
	 * @since 3.5.0
	 *
	 * @param string $item_title Item title.
	 *
	 * @return string
	 */
	private function get_title( $item_title ) {
		return $this->get_label(
			'archive',
			// translators: %s archive item title.
			sprintf( __( 'Archive for %s', 'wds' ), $item_title )
		);
	}
}