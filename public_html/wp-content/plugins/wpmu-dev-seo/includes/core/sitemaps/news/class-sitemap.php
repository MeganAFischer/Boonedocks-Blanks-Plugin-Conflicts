<?php
/**
 * Sitemap class for handling news sitemaps in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Sitemaps\News;

use SmartCrawl\Simple_Renderer;
use SmartCrawl\Sitemaps;

/**
 * Class Sitemap
 *
 * Handles the creation and serving of news sitemaps.
 */
class Sitemap extends Sitemaps\Sitemap {

	/**
	 * Adds rewrite rules for news sitemaps.
	 *
	 * @return void
	 */
	public function add_rewrites() {
		/**
		 * WP.
		 */
		global $wp;

		$wp->add_query_var( 'wds_news_sitemap' );
		$wp->add_query_var( 'wds_news_sitemap_type' );
		$wp->add_query_var( 'wds_news_sitemap_page' );
		$wp->add_query_var( 'wds_news_sitemap_gzip' );

		add_rewrite_rule( '^news-sitemap\.xml(\.gz)?$', 'index.php?wds_news_sitemap=1&wds_news_sitemap_type=index&wds_news_sitemap_gzip=$matches[1]', 'top' );
		add_rewrite_rule( '^news-([^/]+?)-sitemap([0-9]+)?\.xml(\.gz)?$', 'index.php?wds_news_sitemap=1&wds_news_sitemap_type=$matches[1]&wds_news_sitemap_page=$matches[2]&wds_news_sitemap_gzip=$matches[3]', 'top' );
	}

	/**
	 * Checks if the news sitemap is enabled.
	 *
	 * @return bool True if the news sitemap is enabled, false otherwise.
	 */
	public function is_enabled() {
		return parent::is_enabled()
			&& Sitemaps\Utils::get_sitemap_option( 'enable-news-sitemap' );
	}

	/**
	 * Checks if the request can be handled.
	 *
	 * @return bool True if the request can be handled, false otherwise.
	 */
	public function can_handle_request() {
		return (bool) get_query_var( 'wds_news_sitemap' );
	}

	/**
	 * Handles fallback for the news sitemap.
	 *
	 * @return void
	 */
	public function do_fallback() {
		$this->do_404();
	}

	/**
	 * Generates the cache type for the given sitemap type.
	 *
	 * @param string $type The sitemap type.
	 *
	 * @return string The cache type.
	 */
	private function cache_type( $type ) {
		return "news-{$type}";
	}

	/**
	 * Serves the news sitemap.
	 *
	 * @return void
	 */
	public function serve() {
		$sitemap_type = $this->get_sitemap_type_var();
		$sitemap_page = $this->get_sitemap_page_var();

		$sitemap_cache = Sitemaps\Cache::get();
		$cached        = $sitemap_cache->get_cached(
			$this->cache_type( $sitemap_type ),
			$sitemap_page
		);
		$gzip          = $this->is_gzip_request();

		if ( ! empty( $cached ) ) {
			$this->output_xml( $cached, $gzip );

			return;
		}

		do_action( 'wds_before_news_sitemap_rebuild' );

		if ( self::SITEMAP_TYPE_INDEX === $sitemap_type ) {
			$xml = $this->build_index();
		} else {
			$xml = $this->build_partial_sitemap( $sitemap_type, $sitemap_page );
		}

		$sitemap_cache->set_cached(
			$this->cache_type( $sitemap_type ),
			$sitemap_page,
			$xml
		);
		$this->output_xml( $xml, $gzip );
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
		foreach ( $this->get_queries() as $query ) {
			if ( $query->can_handle_type( $type ) ) {
				$items = array_merge(
					$items,
					$query->get_items( $type, $page )
				);
				break;
			}
		}

		$items = apply_filters( 'wds_partial_news_sitemap_items', $items, $type, $page );

		if ( empty( $items ) ) {
			return false;
		}

		return $this->build_xml( $items );
	}

	/**
	 * Post-processes the news sitemap.
	 *
	 * @return void
	 */
	private function post_process() {
		do_action( 'wds_news_sitemap_created' );
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

		$this->post_process();

		return $this->build_index_xml( $index_items );
	}

	/**
	 * Retrieves the sitemap type query variable.
	 *
	 * @return string The sitemap type query variable.
	 */
	private function get_sitemap_type_var() {
		return (string) get_query_var( 'wds_news_sitemap_type' );
	}

	/**
	 * Retrieves the sitemap page query variable.
	 *
	 * @return int The sitemap page query variable.
	 */
	private function get_sitemap_page_var() {
		return (int) get_query_var( 'wds_news_sitemap_page' );
	}

	/**
	 * Checks if the request is for a gzip sitemap.
	 *
	 * @return bool True if the request is for a gzip sitemap, false otherwise.
	 */
	private function is_gzip_request() {
		$query_var = get_query_var( 'wds_news_sitemap_gzip' );

		return ! empty( $query_var );
	}

	/**
	 * Retrieves the queries for the news sitemap.
	 *
	 * @return Query[] The queries.
	 */
	private function get_queries() {
		return array(
			new Query(),
		);
	}

	/**
	 * Builds the XML for the given items.
	 *
	 * @param array $items The items to include in the sitemap.
	 *
	 * @return string The sitemap XML.
	 */
	public function build_xml( $items ) {
		return Simple_Renderer::load(
			'sitemap/sitemap-news-xml',
			array(
				'news_items' => $items,
			)
		);
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
}