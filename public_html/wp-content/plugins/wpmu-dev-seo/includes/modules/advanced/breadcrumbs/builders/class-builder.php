<?php
/**
 * Base class for breadcrumb
 *
 * @since   3.5.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Modules\Advanced\Breadcrumbs\Builders;

use SmartCrawl\Modules\Advanced\Breadcrumbs\Helper;
use SmartCrawl\Endpoint_Resolver;
use SmartCrawl\Singleton;

/**
 * Breadcrumbs class.
 */
abstract class Builder {

	use Singleton;

	/**
	 * Items in breadcrumb.
	 *
	 * @since 3.5.0
	 * @var array $items
	 */
	protected $items = array();

	/**
	 * Get the prepared crumb items for current page.
	 *
	 * @since 3.5.0
	 *
	 * @param bool $force Force update breadcrumb items list.
	 *
	 * @return array
	 */
	public function get_items( $force = false ) {
		// Prepare items if not prepared already.
		$this->maybe_prepare_items( $force );

		/**
		 * Filter to modify the breadcrumb items.
		 *
		 * @since 3.5.0
		 *
		 * @param array $items Prepared items.
		 */
		return apply_filters( 'smartcrawl_breadcrumbs_get_items', $this->items );
	}

	/**
	 * Render the breadcrumbs for the items on the list.
	 *
	 * @since 3.5.0
	 *
	 * @param string $before What to show before the breadcrumb.
	 * @param string $after  What to show after the breadcrumb.
	 *
	 * @return string
	 */
	public function render( $before = '', $after = '' ) {
		// Prepare if not prepared already.
		$this->maybe_prepare_items();

		$output = $this->get_html( $before, $after );

		/**
		 * Filter to modify final breadcrumb html output.
		 *
		 * @since 3.5.0
		 *
		 * @param string $output Generated html.
		 */
		return apply_filters( 'smartcrawl_breadcrumbs_render_output', $output );
	}

	/**
	 * Prepare items if not prepared already.
	 *
	 * @since 3.5.0
	 *
	 * @param bool $force Force update breadcrumb items list.
	 *
	 * @return void
	 */
	public function maybe_prepare_items( $force = false ) {
		// Prepare items if not prepared already.
		if ( empty( $this->items ) || $force ) {
			$this->prepare_items();

			/**
			 * Action hook to run after breadcrumb items are prepared.
			 *
			 * @since 3.5.0
			 *
			 * @param mixed $items Items prepared.
			 */
			do_action( 'smartcrawl_breadcrumbs_after_prepare_items', $this->items );
		}
	}

	/**
	 * Reset breadcrumb items list.
	 *
	 * The home page will be added to the list as first item.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	public function reset_items() {
		if ( Helper::get_option( 'home_trail', true ) ) {
			$this->items = array( $this->get_home_data() );
		} else {
			$this->items = array();
		}
	}

	/**
	 * Prepare items for the breadcrumb.
	 *
	 * Add each items to the list in correct order using
	 * add_item() method.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	abstract protected function prepare_items();

	/**
	 * Get data for home page item.
	 *
	 * This is required as first item of all breadcrumbs.
	 *
	 * @since 3.5.0
	 *
	 * @return array
	 */
	protected function get_home_data() {
		$label         = Helper::get_option( 'home_label' );
		$default_label = _x( 'Home', 'breadcrumb', 'wds' );

		return array(
			'link'  => site_url(),
			'title' => empty( $label ) ? $default_label : wp_strip_all_tags( $label ),
		);
	}

	/**
	 * Build the final html output.
	 *
	 * The items on the breadcrumb should be setup before calling
	 * this method to build html.
	 * Loop through each item on the list and build span tags.
	 *
	 * @since 3.5.0
	 *
	 * @param string $before What to show before the breadcrumb.
	 * @param string $after  What to show after the breadcrumb.
	 *
	 * @return string
	 */
	protected function get_html( $before = '', $after = '' ) {
		return sprintf(
			'%s%s%s',
			empty( $before ) ? '' : wp_kses_post( $before ),
			$this->get_breadcrumb_html(),
			empty( $after ) ? '' : wp_kses_post( $after )
		);
	}

	/**
	 * Build the final html output.
	 *
	 * The items on the breadcrumb should be setup before calling
	 * this method to build html.
	 * Loop through each item on the list and build span tags.
	 *
	 * @since 3.5.0
	 *
	 * @return string
	 */
	protected function get_breadcrumb_html() {
		// Nothing to render.
		if ( empty( $this->items ) ) {
			return '';
		}

		$counter = 1;
		// Total items.
		$item_count = count( $this->items );

		// Append prefix if required.
		$breadcrumb_html = $this->maybe_add_prefix();

		foreach ( $this->items as $item ) {
			// Get crumb item.
			$breadcrumb_html = $this->add_crumb_item_html( $breadcrumb_html, $item );

			// Append separator if not the last item.
			if ( $counter < $item_count ) {
				$breadcrumb_html = $this->add_separator( $breadcrumb_html );
			}

			++$counter;
		}

		return $this->add_main_wrapper( $breadcrumb_html );
	}

	/**
	 * Get a single breadcrumb child item.
	 *
	 * If the item is current page, we will not add link to it.
	 * Or if there is no link found, we will add it as a label.
	 *
	 * @since 3.5.0
	 *
	 * @param string $html Breadcrumb HTML.
	 * @param array  $item Item data.
	 *
	 * @return string
	 */
	protected function add_crumb_item_html( $html, $item ) {
		if ( ! empty( $item['link'] ) ) {
			// If not current active page, create link.
			$content = sprintf(
				'<a href="%s" title="%s">%s</a>',
				$item['link'],
				$item['title'],
				$item['title']
			);
		} else {
			// If current page, a label is enough.
			$content = sprintf( '<strong>%s</strong>', $item['title'] );
		}

		// Get item html.
		$item_html = sprintf( '<span class="smartcrawl-breadcrumb">%s</span>', $content );

		/**
		 * Filter to modify breadcrumb item html.
		 *
		 * @since 3.5.0
		 *
		 * @param string $item_html Breadcrumb item html.
		 * @param array  $item      Breadcrumb item data.
		 */
		$html .= apply_filters( 'smartcrawl_breadcrumbs_item_html', $item_html, $item );

		return $html;
	}

	/**
	 * Get breadcrumb main element.
	 *
	 * @since 3.5.0
	 *
	 * @param string $html Breadcrumb HTML.
	 *
	 * @return string
	 */
	protected function add_main_wrapper( $html ) {
		return sprintf( '<div class="smartcrawl-breadcrumbs">%s</div>', $html );
	}

	/**
	 * Add prefix to the breadcrumb if required.
	 *
	 * @since 3.5.0
	 *
	 * @param string $html Breadcrumb HTML.
	 *
	 * @return string
	 */
	protected function maybe_add_prefix( $html = '' ) {
		$prefix      = Helper::get_option( 'prefix' );
		$show_prefix = Helper::get_option( 'add_prefix' );

		// Append prefix if required.
		if ( $show_prefix && ! empty( $prefix ) ) {
			$allowed_tags = wp_kses_allowed_html( 'entities' );
			$prefix       = wp_kses( $prefix, $allowed_tags );
			// If valid prefix found.
			if ( ! empty( $prefix ) ) {
				$html .= "\t" . wp_kses( $prefix, $allowed_tags ) . "\n";
			}
		}

		return $html;
	}

	/**
	 * Add separator after the item.
	 *
	 * @since 3.5.0
	 *
	 * @param string $html Breadcrumb HTML.
	 *
	 * @return string
	 */
	protected function add_separator( $html ) {
		// Append separator.
		$html .= sprintf(
			'<span class="smartcrawl-breadcrumb-separator">%s</span>',
			' ' . esc_attr( Helper::get_separator() ) . ' '
		);

		return $html;
	}

	/**
	 * Add an item to the breadcrumb item list.
	 *
	 * @since 3.5.0
	 *
	 * @param array $item Item data.
	 *
	 * @return void
	 */
	protected function add_item( array $item ) {
		if ( ! empty( $item['title'] ) ) {
			/**
			 * Filter to modify breadcrumb item data.
			 *
			 * @since 3.5.0
			 *
			 * @param array $item Breadcrumb item data.
			 */
			$item = apply_filters( 'smartcrawl_breadcrumbs_item_data', $item );

			$this->items[] = wp_parse_args(
				$item,
				array(
					'link'  => '',
					'title' => '',
				)
			);
		}
	}

	/**
	 * Add an item to the breadcrumb item list.
	 *
	 * @since 3.5.0
	 *
	 * @param array $item Item data.
	 *
	 * @return void
	 */
	protected function add_item_with_paged( array $item ) {
		$is_paged = is_paged();

		// Remove link.
		if ( ! $is_paged ) {
			$item['link'] = '';
		}

		// Set current term to crumb.
		$this->add_item( $item );

		// If paged, set paged crumb item.
		if ( $is_paged ) {
			$this->add_item(
				array(
					// translators: %d current page number.
					'title' => sprintf( __( 'Page %d', 'wds' ), get_query_var( 'paged' ) ),
				)
			);
		}
	}

	/**
	 * Get label for a breadcrumb.
	 *
	 * @since 3.5.0
	 *
	 * @param string $type  Label type.
	 * @param mixed  $label Default label.
	 *
	 * @return mixed
	 */
	protected function get_label( $type, $label = '' ) {
		$labels = Helper::get_option( 'labels', array() );

		if ( ! empty( $labels[ $type ] ) ) {
			// Replace macros if required.
			$label = $this->replace_macros( $labels[ $type ] );
		}

		/**
		 * Filter to modify breadcrumbs item label.
		 *
		 * @since 3.5.0
		 *
		 * @param mixed  $label Label value.
		 * @param string $type  Label type.
		 */
		return apply_filters( 'smartcrawl_breadcrumbs_get_label', $label, $type );
	}

	/**
	 * Replace macros using the entity class.
	 *
	 * @since 3.5.0
	 *
	 * @param string $string String to process.
	 *
	 * @return mixed
	 */
	protected function replace_macros( $string ) {
		$entity = Endpoint_Resolver::resolve()->get_queried_entity();
		if ( ! $entity ) {
			return $string;
		}

		return $entity->apply_macros( $string, 'breadcrumb' );
	}

	/**
	 * Set ancestor items for breadcrumb.
	 *
	 * This can be used for both taxonomies and posts.
	 *
	 * @since 3.5.0
	 *
	 * @param int    $object_id     The ID of the object.
	 * @param string $object_type   post type or a taxonomy name.
	 * @param string $resource_type 'post_type' or 'taxonomy'.
	 *
	 * @return void
	 */
	protected function set_ancestor_crumbs( $object_id, $object_type, $resource_type = 'taxonomy' ) {
		// Check if there are parent items.
		$ancestors = get_ancestors( $object_id, $object_type, $resource_type );

		if ( ! empty( $ancestors ) ) {
			foreach ( array_reverse( $ancestors ) as $ancestor ) {
				// Do not continue if not a valid term if type is taxonomy.
				$term = 'taxonomy' === $resource_type ? get_term( $ancestor, $object_type ) : false;
				if ( 'taxonomy' === $resource_type && ! $term instanceof \WP_Term ) {
					continue;
				}

				$this->add_item(
					array(
						'link'  => 'taxonomy' === $resource_type ? get_term_link( $ancestor, $object_type ) : get_the_permalink( $ancestor ),
						'title' => 'taxonomy' === $resource_type ? $term->name : get_the_title( $ancestor ),
					)
				);
			}
		}
	}
}