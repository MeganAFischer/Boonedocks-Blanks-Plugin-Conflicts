<?php
/**
 * Controller class for handling WPML integration.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\WPML;

use SmartCrawl\Singleton;
use SmartCrawl\Controllers;

/**
 * Class Controller
 *
 * Manages WPML integration and related hooks.
 */
class Controller extends Controllers\Controller {

	use Singleton;

	/**
	 * WPML API instance.
	 *
	 * @var Api
	 */
	private $wpml_api;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		parent::__construct();

		$this->wpml_api = new Api();
	}

	/**
	 * Checks if WPML is active.
	 *
	 * @return bool True if WPML is active, false otherwise.
	 */
	public function should_run() {
		return class_exists( '\SitePress' );
	}

	/**
	 * Initializes the controller.
	 */
	protected function init() {
		add_action( 'plugins_loaded', array( $this, 'hook_with_wpml' ) );
	}

	/**
	 * Hooks the controller with WPML.
	 */
	public function hook_with_wpml() {
		global $sitepress;
		if ( empty( $sitepress ) ) {
			return;
		}

		add_action( 'wds_post_readability_language', array( $this, 'change_post_analysis_language' ), 10, 2 );
		add_action( 'wds_post_seo_analysis_language', array( $this, 'change_post_analysis_language' ), 10, 2 );

		$strategy                 = $this->wpml_api->get_setting( 'language_negotiation_type' );
		$separate_domain_per_lang = 2 === $strategy;
		if ( $separate_domain_per_lang ) {
			$this->sitemap_for_each_domain();
		} else {
			$this->fix_duplicate_urls();
		}
	}

	/**
	 * If the user has a separate domain for each language we need to make sure that each domain serves a unique sitemap only containing URLs belonging to that domain
	 */
	private function sitemap_for_each_domain() {
		add_filter(
			'wds_posts_sitemap_include_post_ids',
			array(
				$this,
				'limit_sitemap_posts_by_language',
			),
			10,
			2
		);
		add_filter( 'wds_terms_sitemap_include_term_ids', array( $this, 'limit_sitemap_terms_by_language' ), 10, 2 );
		add_filter( 'wds_news_sitemap_include_post_ids', array( $this, 'limit_sitemap_posts_by_language' ), 10, 2 );
		add_filter( 'wds_sitemap_cache_file_name', array( $this, 'append_language_code_to_cache' ) );
	}

	/**
	 * Limits sitemap terms by language.
	 *
	 * @param array $include_ids Term IDs to include.
	 * @param array $taxonomies  Taxonomies to query.
	 *
	 * @return array Filtered term IDs.
	 */
	public function limit_sitemap_terms_by_language( $include_ids, $taxonomies ) {
		$term_query = new \WP_Term_Query(
			array(
				'taxonomy' => $taxonomies,
				'fields'   => 'ids',
			)
		);

		$term_ids = $term_query->get_terms();
		if ( empty( $term_ids ) ) {
			$term_ids = array( - 1 );
		}
		$include_ids = empty( $include_ids ) || ! is_array( $include_ids )
			? array()
			: $include_ids;

		return array_merge( $include_ids, $term_ids );
	}

	/**
	 * Limits sitemap posts by language.
	 *
	 * @param array $include_ids Post IDs to include.
	 * @param array $post_types  Post types to query.
	 *
	 * @return array Filtered post IDs.
	 */
	public function limit_sitemap_posts_by_language( $include_ids, $post_types ) {
		$query = new \WP_Query(
			array(
				'post_type'        => $post_types,
				'posts_per_page'   => - 1,
				'post_status'      => 'publish',
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);

		$post_ids = $query->get_posts();
		if ( empty( $post_ids ) ) {
			$post_ids = array( - 1 );
		}
		$include_ids = empty( $include_ids ) || ! is_array( $include_ids )
			? array()
			: $include_ids;

		return array_merge( $include_ids, $post_ids );
	}

	/**
	 * Appends language code to cache file name.
	 *
	 * @param string $file_name Original file name.
	 *
	 * @return string Modified file name with language code.
	 */
	public function append_language_code_to_cache( $file_name ) {
		$current_lang = apply_filters( 'wpml_current_language', null );

		return "$current_lang-$file_name";
	}

	/**
	 * WPML tries to 'translate' urls but in our context it leads to every URL getting converted to the default language.
	 *
	 * If the post ID of an Urdu post is passed to get_permalink, we expect to get the Urdu url in return but the conversion changes it to default language URL.
	 */
	private function fix_duplicate_urls() {
		add_filter( 'wds_before_sitemap_rebuild', array( $this, 'add_permalink_filters' ) );
		add_filter( 'wds_sitemap_created', array( $this, 'remove_permalink_filters' ) );
		add_filter( 'wds_full_sitemap_items', array( $this, 'add_homepage_versions' ) );
		add_filter( 'wds_partial_sitemap_items', array( $this, 'add_homepage_versions_to_partial' ), 10, 3 );
	}

	/**
	 * Adds homepage versions to partial sitemap.
	 *
	 * @param array  $items       Sitemap items.
	 * @param string $type        Sitemap type.
	 * @param int    $page_number Page number.
	 *
	 * @return array Modified sitemap items.
	 */
	public function add_homepage_versions_to_partial( $items, $type, $page_number ) {
		$is_first_post_sitemap = ( 'post' === $type || 'page' === $type ) && 1 === $page_number;
		if ( ! $is_first_post_sitemap ) {
			return $items;
		}

		return $this->add_homepage_versions( $items );
	}

	/**
	 * Adds homepage versions to sitemap.
	 *
	 * @param array $items Sitemap items.
	 *
	 * @return array Modified sitemap items.
	 */
	public function add_homepage_versions( $items ) {
		// Remove the original home url.
		array_shift( $items );

		// Add all homepage versions.
		$languages = $this->wpml_api->get_active_languages( false, true );
		foreach ( $languages as $language_code => $language ) {
			if ( $this->wpml_api->get_default_language() === $language_code ) {
				continue;
			}

			$item_url = $this->wpml_api->convert_url( home_url(), $language_code );
			array_unshift(
				$items,
				$this->get_sitemap_homepage_item( $item_url )
			);
		}

		array_unshift(
			$items,
			$this->get_sitemap_homepage_item( home_url( '/' ) )
		);

		return $items;
	}

	/**
	 * Adds permalink filters.
	 */
	public function add_permalink_filters() {
		$callback = array( $this, 'translate_post_url' );

		add_filter( 'post_link', $callback, 10, 2 );
		add_filter( 'page_link', $callback, 10, 2 );
		add_filter( 'post_type_link', $callback, 10, 2 );
	}

	/**
	 * Removes permalink filters.
	 */
	public function remove_permalink_filters() {
		$callback = array( $this, 'translate_post_url' );

		remove_filter( 'post_link', $callback );
		remove_filter( 'page_link', $callback );
		remove_filter( 'post_type_link', $callback );
	}

	/**
	 * Translates post URL to the current language.
	 *
	 * @param string   $link       Original link.
	 * @param \WP_Post $post_or_id Post object or ID.
	 *
	 * @return string Translated link.
	 */
	public function translate_post_url( $link, $post_or_id ) {
		$post          = get_post( $post_or_id );
		$language      = $this->wpml_api->wpml_get_language_information( null, $post->ID );
		$language_code = \smartcrawl_get_array_value( $language, 'language_code' );
		if ( $this->wpml_api->get_current_language() === $language_code ) {
			return $link;
		}

		$this->remove_permalink_filters(); // To avoid infinite recursion.
		$language_url = apply_filters( 'wpml_permalink', get_permalink( $post->ID ), $language_code, true );
		$this->add_permalink_filters();

		return $language_url;
	}

	/**
	 * Gets sitemap homepage item.
	 *
	 * @param string $url URL of the homepage.
	 *
	 * @return \SmartCrawl\Sitemaps\General\Item Sitemap item.
	 */
	private function get_sitemap_homepage_item( $url ) {
		$item = new \SmartCrawl\Sitemaps\General\Item();

		return $item->set_location( $url );
	}

	/**
	 * Changes post analysis language.
	 *
	 * @param string $post_language Current post language.
	 * @param int    $post_id       Post ID.
	 *
	 * @return string Modified post language.
	 */
	public function change_post_analysis_language( $post_language, $post_id ) {
		$wpml_lang_code = $this->get_post_language_code( $post_id );

		return ! empty( $wpml_lang_code )
			? $wpml_lang_code
			: $post_language;
	}

	/**
	 * We would rather use wpml_get_language_information, but it has internal caching that doesn't get purged the first time a post is saved.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string|null
	 */
	private function get_post_language_code( $post_id ) {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE element_id = %d", $post_id ) );
	}
}