<?php
/**
 * Sitemap class for handling general sitemaps in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Sitemaps\General;

use SmartCrawl\Simple_Renderer;
use SmartCrawl\Sitemaps;
use SmartCrawl\Sitemaps\Cache;
use SmartCrawl\Sitemaps\Query;
use SmartCrawl\Sitemaps\Utils;

/**
 * Class Sitemap
 *
 * Handles the creation and serving of general sitemaps.
 */
class Sitemap extends Sitemaps\Sitemap {

	/**
	 * Adds rewrite rules for general sitemaps.
	 *
	 * @return void
	 */
	public function add_rewrites() {
		/**
		 * WP.
		 */
		global $wp;

		$wp->add_query_var( 'wds_sitemap' );
		$wp->add_query_var( 'wds_sitemap_type' );
		$wp->add_query_var( 'wds_sitemap_page' );
		$wp->add_query_var( 'wds_sitemap_gzip' );

		add_rewrite_rule( '^sitemap\.xml(\.gz)?$', 'index.php?wds_sitemap=1&wds_sitemap_type=index&wds_sitemap_gzip=$matches[1]', 'top' );
		add_rewrite_rule( '^([^/]+?)-sitemap([0-9]+)?\.xml(\.gz)?$', 'index.php?wds_sitemap=1&wds_sitemap_type=$matches[1]&wds_sitemap_page=$matches[2]&wds_sitemap_gzip=$matches[3]', 'top' );
	}

	/**
	 * Checks if the request can be handled.
	 *
	 * @return bool True if the request can be handled, false otherwise.
	 */
	public function can_handle_request() {
		return (bool) get_query_var( 'wds_sitemap' );
	}

	/**
	 * Handles fallback for the general sitemap.
	 *
	 * @return void
	 */
	public function do_fallback() {
		$this->maybe_redirect_to_native( $this->native_sitemap_available() );
	}

	/**
	 * Serves the general sitemap.
	 *
	 * @return void
	 */
	public function serve() {
		$native_available = $this->native_sitemap_available();
		$override_native  = Utils::override_native();
		if ( ! $override_native && $native_available ) {
			$this->redirect_to_native();
			return;
		}

		$sitemap_type = $this->get_sitemap_type_var();
		$sitemap_page = $this->get_sitemap_page_var();

		$sitemap_cache = Cache::get();
		$cached        = $sitemap_cache->get_cached( $sitemap_type, $sitemap_page );
		$gzip          = $this->is_gzip_request();

		if ( ! empty( $cached ) ) {
			$this->output_xml( $cached, $gzip );
			return;
		}

		do_action( 'wds_before_sitemap_rebuild' );

		if ( self::SITEMAP_TYPE_INDEX === $sitemap_type ) {
			$xml = $this->build_index();
		} else {
			$xml = $this->build_partial_sitemap( $sitemap_type, $sitemap_page );
		}

		if ( ! $xml ) {
			$this->maybe_redirect_to_native( $native_available );
			return;
		}

		$sitemap_cache->set_cached( $sitemap_type, $sitemap_page, $xml );
		$this->output_xml( $xml, $gzip );
	}

	/**
	 * Redirects to native sitemap if available.
	 *
	 * @param bool $native_available True if native sitemap is available, false otherwise.
	 *
	 * @return void
	 */
	private function maybe_redirect_to_native( $native_available ) {
		if ( $native_available ) {
			$this->redirect_to_native();
		} else {
			$this->do_404();
		}
	}

	/**
	 * Redirects to the native sitemap.
	 *
	 * @return void
	 */
	private function redirect_to_native() {
		/**
		 * Global variable for accessing the WP Sitemaps instance.
		 *
		 * @var $wp_sitemaps \WP_Sitemaps
		 */
		global $wp_sitemaps;

		wp_safe_redirect( $wp_sitemaps->index->get_index_url() );
		die();
	}

	/**
	 * Builds a partial sitemap for the given type and page.
	 *
	 * @param string $type The sitemap type.
	 * @param int    $page The sitemap page number.
	 *
	 * @return false|string The partial sitemap XML or false if no items.
	 */
	private function build_partial_sitemap( $type, $page ) {
		$items = array();
		if ( 'post' === $type && 1 === $page ) {
			$items[] = $this->make_home_page_item();
		}

		if ( 'page' === $type && 1 === $page ) {
			$items[] = $this->make_home_page_item();
		}

		foreach ( $this->get_queries() as $query ) {
			if ( $query->can_handle_type( $type ) ) {
				$items = array_merge(
					$items,
					$query->get_items( $type, $page )
				);
				break;
			}
		}

		$items = apply_filters( 'wds_partial_sitemap_items', $items, $type, $page );

		if ( empty( $items ) ) {
			return false;
		}

		return $this->build_xml( $items );
	}

	/**
	 * Builds the index sitemap.
	 *
	 * @return string The index sitemap XML.
	 */
	private function build_index() {
		$index_items = array();

		foreach ( $this->get_queries() as $query ) {
			$index_items = array_merge(
				$index_items,
				$query->get_index_items()
			);
		}

		$this->post_process( $index_items );

		return $this->build_index_xml( $index_items );
	}

	/**
	 * Retrieves the sitemap type query variable.
	 *
	 * @return string The sitemap type query variable.
	 */
	private function get_sitemap_type_var() {
		return (string) get_query_var( 'wds_sitemap_type' );
	}

	/**
	 * Retrieves the sitemap page query variable.
	 *
	 * @return int The sitemap page query variable.
	 */
	private function get_sitemap_page_var() {
		return (int) get_query_var( 'wds_sitemap_page' );
	}

	/**
	 * Checks if the request is for a gzip sitemap.
	 *
	 * @return bool True if the request is for a gzip sitemap, false otherwise.
	 */
	private function is_gzip_request() {
		$query_var = get_query_var( 'wds_sitemap_gzip' );
		return ! empty( $query_var );
	}

	/**
	 * Retrieves the queries for the general sitemap.
	 *
	 * @return Query[] The queries.
	 */
	private function get_queries() {
		$query_classes = array();

		$queries = array(
			new Queries\Posts(),
			new Queries\Terms(),
			new Queries\BP_Groups(),
			new Queries\BP_Profile(),
			new Queries\Extras(),
		);

		/**
		 * Filter hook to add or remove sitemaps on the fly.
		 *
		 * @param Query[] $queries Sitemaps.
		 */
		$queries = apply_filters( 'smartcrawl_sitemaps_general_get_queries', $queries );

		foreach ( $queries as $query ) {
			// Should be a valid sitemap class.
			if ( is_a( $query, '\SmartCrawl\Sitemaps\Query', true ) ) {
				$query_classes[] = $query;
			}
		}

		return $query_classes;
	}

	/**
	 * Post-processes the general sitemap.
	 *
	 * @param array $items The items to include in the sitemap.
	 *
	 * @return void
	 */
	private function post_process( $items ) {
		do_action( 'wds_sitemap_created' );
		Utils::notify_engines();
		Utils::update_meta_data( count( $items ) );
	}

	/**
	 * Creates a sitemap item for the home page.
	 *
	 * @return Item The home page item.
	 */
	private function make_home_page_item() {
		$item = new Item();
		$item->set_location( home_url( '/' ) );

		return $item;
	}

	/**
	 * Builds the index XML for the given index items.
	 *
	 * @param array $index_items The index items to include in the sitemap.
	 *
	 * @return string The index sitemap XML.
	 */
	private function build_index_xml( $index_items ) {
		return Simple_Renderer::load(
			'sitemap/sitemap-index-xml',
			array(
				'index_items' => $index_items,
			)
		);
	}

	/**
	 * Builds the XML for the given items.
	 *
	 * @param array $items The items to include in the sitemap.
	 *
	 * @return string The sitemap XML.
	 */
	private function build_xml( $items ) {
		return Simple_Renderer::load(
			'sitemap/sitemap-general-xml',
			array(
				'items' => $items,
			)
		);
	}

	/**
	 * Checks if the native sitemap is available.
	 *
	 * @return bool True if the native sitemap is available, false otherwise.
	 */
	private function native_sitemap_available() {
		return Utils::native_sitemap_available();
	}
}