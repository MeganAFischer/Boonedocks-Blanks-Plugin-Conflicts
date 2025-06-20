<?php
/**
 * Breadcrumb builder for posts.
 *
 * @since   3.5.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Modules\Advanced\Breadcrumbs\Builders;

use SmartCrawl\Modules\Advanced\Breadcrumbs\Helper;
use SmartCrawl\Controllers\Primary_Terms;

/**
 * Pages breadcrumb class.
 */
class Posts extends Builder {

	/**
	 * Build items for breadcrumb.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	protected function prepare_items() {
		$this->reset_items();

		if ( is_post_type_archive() ) {
			// Setup archive crumb.
			$this->maybe_set_post_archive_crumb();
		} else {
			// Setup single post crumbs.
			$this->prepare_single();
		}
	}

	/**
	 * Build items for single post breadcrumbs.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	protected function prepare_single() {
		if ( 'post' === get_post_type() ) {
			// Setup blog page crumb.
			$this->maybe_set_posts_page_crumb();
			// Set category crumbs.
			$this->set_category_crumbs();
		} else {
			// Setup archive crumb.
			$this->maybe_set_post_archive_crumb( false );
			// Set post parents crumbs.
			$this->maybe_set_post_ancestors_crumbs();
		}

		// Set current post crumb.
		if ( ! Helper::get_option( 'hide_post_title' ) ) {
			$this->add_item(
				array(
					'title' => $this->get_label( 'post', get_the_title() ),
				)
			);
		}
	}

	/**
	 * Set crumbs for post ancestor items.
	 *
	 * If the post is hierarchical and there are ancestors,
	 * set them to the crumbs item list.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	protected function maybe_set_post_ancestors_crumbs() {
		global $post;

		$this->set_ancestor_crumbs(
			$post->ID,
			get_post_type(),
			'post_type'
		);
	}

	/**
	 * Set crumbs for post ancestor items.
	 *
	 * If the post is hierarchical and there are ancestors,
	 * set them to the crumbs item list.
	 *
	 * @since 3.5.0
	 *
	 * @param bool $archive Is it being called on an archive page?.
	 *
	 * @return void
	 */
	protected function maybe_set_post_archive_crumb( $archive = true ) {
		$post_type_name = get_post_type();
		$post_type      = get_post_type_object( $post_type_name );

		// If archive is enabled.
		if ( ! empty( $post_type ) && $post_type->has_archive ) {
			$item = array(
				'link'  => get_post_type_archive_link( $post_type_name ),
				'title' => $archive ? $this->get_archive_title( $post_type->labels->name ) : $post_type->labels->name,
			);

			$archive ? $this->add_item_with_paged( $item ) : $this->add_item( $item );
		}
	}

	/**
	 * Set crumbs for post ancestor items.
	 *
	 * If the post is hierarchical and there are ancestors,
	 * set them to the crumbs item list.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	protected function maybe_set_posts_page_crumb() {
		// Get blog page if required.
		$blog_page = get_option( 'page_for_posts' );
		if ( ! empty( $blog_page ) ) {
			// Set blog page crumb.
			$this->add_item(
				array(
					'link'  => get_the_permalink( $blog_page ),
					'title' => get_the_title( $blog_page ),
				)
			);
		}
	}

	/**
	 * Set crumbs for category items.
	 *
	 * If there is a primary category set, we will always use it or else
	 * we will use the first item from the assigned category.
	 * If a the primary category has a parent, we will include that too.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	protected function set_category_crumbs() {
		$category = $this->get_primary_category();

		if ( $category instanceof \WP_Term ) {
			// Set ancestor crumbs.
			$this->set_ancestor_crumbs( $category->term_id, 'category' );

			// Add primary category crumb.
			$this->add_item(
				array(
					'link'  => get_category_link( $category->term_id ),
					'title' => $category->name,
				)
			);
		}
	}

	/**
	 * Get primary category of the post.
	 *
	 * If there is no primary category, use the first assigned category
	 * as primary.
	 *
	 * @since 3.5.0
	 *
	 * @return false|\WP_Term
	 */
	protected function get_primary_category() {
		global $post;

		// Is primary category feature active.
		$primary_terms_active = Primary_Terms::get()->should_run();

		// Get primary category.
		if ( $primary_terms_active ) {
			$category = get_post_meta( $post->ID, 'wds_primary_category', true );
			if ( ! empty( $category ) ) {
				$category = get_term( $category, 'category' );
			}
		}

		// Primary category is not available, get first category.
		if ( ! $primary_terms_active || empty( $category ) ) {
			$categories = get_the_category( $post->ID );

			// First item is the primary category.
			$category = empty( $categories ) ? false : $categories[0];
		}

		return $category;
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
	private function get_archive_title( $item_title ) {
		return $this->get_label(
			'archive',
			// translators: %s archive item title.
			sprintf( __( 'Archive for %s', 'wds' ), $item_title )
		);
	}
}