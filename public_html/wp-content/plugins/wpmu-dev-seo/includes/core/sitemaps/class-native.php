<?php
/**
 * Native class for handling native sitemap-related services in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Sitemaps;

use SmartCrawl\Singleton;
use SmartCrawl\Controllers;
use SmartCrawl\Sitemaps\General\Queries;

/**
 * Class Native
 *
 * Handles native sitemap-related services.
 */
class Native extends Controllers\Controller {

	use Singleton;

	/**
	 * Query object for handling posts.
	 *
	 * @var Queries\Posts
	 */
	private $posts_query;

	/**
	 * Query object for handling terms.
	 *
	 * @var Queries\Terms
	 */
	private $terms_query;

	/**
	 * Query object for handling BuddyPress profiles.
	 *
	 * @var Queries\BP_Profile
	 */
	private $bp_profile_query;

	/**
	 * Query object for handling BuddyPress groups.
	 *
	 * @var Queries\BP_Groups
	 */
	private $bp_groups_query;

	/**
	 * Query object for handling extra queries.
	 *
	 * @var Queries\Extras
	 */
	private $extras_query;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		parent::__construct();

		$this->posts_query      = new Queries\Posts();
		$this->terms_query      = new Queries\Terms();
		$this->bp_profile_query = new Queries\BP_Profile();
		$this->bp_groups_query  = new Queries\BP_Groups();
		$this->extras_query     = new Queries\Extras();
	}

	/**
	 * Determines if the native sitemap should run.
	 *
	 * @return bool True if the native sitemap should run, false otherwise.
	 */
	public function should_run() {
		return ! Utils::override_native();
	}

	/**
	 * Initializes the native sitemap.
	 *
	 * @return void
	 */
	protected function init() {
		add_action( 'init', array( $this, 'hook' ), 15 ); // Give native sitemaps a chance to initialize properly.
	}

	/**
	 * Hooks into WordPress actions and filters.
	 *
	 * @return void
	 */
	public function hook() {
		if ( ! \SmartCrawl\Sitemaps\Utils::native_sitemap_available() ) {
			return;
		}

		add_filter( 'wp_sitemaps_post_types', array( $this, 'filter_post_types' ) );
		add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'exclude_post_ids' ), 10, 2 );
		add_filter( 'wp_sitemaps_posts_entry', array( $this, 'replace_post_url_with_canonical' ), 10, 2 );

		add_filter( 'wp_sitemaps_taxonomies', array( $this, 'filter_taxonomies' ) );
		add_filter( 'wp_sitemaps_taxonomies_query_args', array( $this, 'exclude_term_ids' ), 10, 2 );
		add_filter( 'wp_sitemaps_taxonomies_entry', array( $this, 'replace_term_url_with_canonical' ), 10, 3 );

		add_filter( 'wp_sitemaps_max_urls', array( '\SmartCrawl\Sitemaps\Utils', 'get_items_per_sitemap' ) );

		$this->register_providers();
	}

	/**
	 * Filters the post types for the sitemap.
	 *
	 * @param array $post_types The post types to filter.
	 *
	 * @return array The filtered post types.
	 */
	public function filter_post_types( $post_types ) {
		return array_filter(
			$post_types,
			array( '\SmartCrawl\Sitemaps\Utils', 'is_post_type_included' ),
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * Excludes specific post IDs from the sitemap.
	 *
	 * @param array  $query_args The query arguments.
	 * @param string $post_type  The post type.
	 *
	 * @return array The modified query arguments.
	 */
	public function exclude_post_ids( $query_args, $post_type ) {
		$query_args['post__not_in'] = array_merge(
			$this->posts_query->get_ignore_ids( $post_type ),
			$this->get_redirected_and_noindex_post_ids( $post_type )
		);

		return $query_args;
	}

	/**
	 * Retrieves post IDs that are redirected or noindex.
	 *
	 * @param string $types The post types.
	 *
	 * @return int[]|\WP_Post[] The post IDs.
	 */
	private function get_redirected_and_noindex_post_ids( $types ) {
		return get_posts(
			array(
				'fields'     => 'ids',
				'post_type'  => $types,
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'OR',
					array(
						'key'     => '_wds_redirect',
						'value'   => '',
						'compare' => '!=',
					),
					array(
						'key'     => '_wds_meta-robots-noindex',
						'value'   => 1,
						'compare' => '=',
					),
				),
			)
		);
	}

	/**
	 * Replaces the post URL with the canonical URL in the sitemap entry.
	 *
	 * @param array    $sitemap_entry The sitemap entry.
	 * @param \WP_Post $post          The post object.
	 *
	 * @return array The modified sitemap entry.
	 */
	public function replace_post_url_with_canonical( $sitemap_entry, $post ) {
		$canonical = \smartcrawl_get_value( 'canonical', $post->ID );
		if ( $canonical ) {
			$sitemap_entry['loc'] = $canonical;
		}

		return $sitemap_entry;
	}

	/**
	 * Filters the taxonomies for the sitemap.
	 *
	 * @param array $taxonomies The taxonomies to filter.
	 *
	 * @return array The filtered taxonomies.
	 */
	public function filter_taxonomies( $taxonomies ) {
		return array_filter(
			$taxonomies,
			array( '\SmartCrawl\Sitemaps\Utils', 'is_taxonomy_included' ),
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * Excludes specific term IDs from the sitemap.
	 *
	 * @param array  $args     The query arguments.
	 * @param string $taxonomy The taxonomy.
	 *
	 * @return array The modified query arguments.
	 */
	public function exclude_term_ids( $args, $taxonomy ) {
		$ignored_ids = $this->terms_query->get_ignored_ids( $taxonomy );

		if ( $ignored_ids ) {
			$args['exclude'] = implode( ',', $ignored_ids );
		}

		return $args;
	}

	/**
	 * Replaces the term URL with the canonical URL in the sitemap entry.
	 *
	 * @param array    $sitemap_entry The sitemap entry.
	 * @param \WP_Term $term          The term object or ID.
	 * @param string   $taxonomy      The taxonomy.
	 *
	 * @return array The modified sitemap entry.
	 */
	public function replace_term_url_with_canonical( $sitemap_entry, $term, $taxonomy ) {
		if ( is_numeric( $term ) ) {
			$term = get_term( $term, $taxonomy );
		}

		$canonical = \smartcrawl_get_term_meta( $term, $taxonomy, 'wds_canonical' );
		if ( $canonical ) {
			$sitemap_entry['loc'] = $canonical;
		}

		return $sitemap_entry;
	}

	/**
	 * Registers sitemap providers.
	 *
	 * @return void
	 */
	public function register_providers() {
		if ( $this->bp_profile_query->can_handle_type( 'bp_profile' ) ) {
			wp_register_sitemap_provider(
				'bp-profile',
				new Provider( 'bp-profile', $this->bp_profile_query )
			);
		}
		if ( $this->bp_groups_query->can_handle_type( 'bp_groups' ) ) {
			wp_register_sitemap_provider(
				'bp-groups',
				new Provider( 'bp-groups', $this->bp_groups_query )
			);
		}
		wp_register_sitemap_provider(
			'extras',
			new Provider( 'extras', $this->extras_query )
		);
	}
}