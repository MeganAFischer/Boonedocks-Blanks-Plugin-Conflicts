<?php
/**
 * Breadcrumb builder for WooCommerce.
 *
 * @since   3.5.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Modules\Advanced\Breadcrumbs\Builders;

/**
 * Woocommerce breadcrumb class.
 */
class Woocommerce extends Builder {

	/**
	 * Build items for breadcrumb.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	protected function prepare_items() {
		$this->reset_items();

		// Shop page crumb.
		$this->set_shop_crumb();

		if ( is_singular( 'product' ) ) {
			// Set current product crumb.
			$this->add_item( array( 'title' => get_the_title() ) );
		} elseif ( is_tax( array( 'product_cat', 'product_tag' ) ) ) {
			// Product category and tag crumbs.
			$this->set_taxonomy_crumbs();
		}
	}

	/**
	 * Build items for single product.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	protected function set_taxonomy_crumbs() {
		// Current term.
		$term = get_queried_object();
		if ( ! $term instanceof \WP_Term ) {
			return;
		}

		// Set current product crumb.
		$this->set_ancestor_crumbs( $term->term_id, $term->taxonomy );

		// Set current term to crumb.
		$this->add_item_with_paged(
			array(
				'link'  => get_term_link( $term->term_id ),
				'title' => $term->name,
			)
		);
	}

	/**
	 * Set items for shop page.
	 *
	 * @since 3.5.0
	 *
	 * @param bool $paginate Check for pagination.
	 *
	 * @return void
	 */
	protected function set_shop_crumb( $paginate = false ) {
		$shop_page = function_exists( '\wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : 0;
		// Fallback in case Woo function doesn't exist.
		if ( empty( $shop_page ) ) {
			$post_type = get_post_type_object( 'product' );

			// If archive is enabled.
			if ( ! empty( $post_type ) ) {
				$item = array(
					'link'  => get_post_type_archive_link( 'product' ),
					'title' => $post_type->labels->name,
				);

				$paginate ? $this->add_item_with_paged( $item ) : $this->add_item( $item );
			}
		} else {
			// Set shop page details.
			$item = array(
				'link'  => get_the_permalink( $shop_page ),
				'title' => get_the_title( $shop_page ),
			);

			$paginate ? $this->add_item_with_paged( $item ) : $this->add_item( $item );
		}
	}
}