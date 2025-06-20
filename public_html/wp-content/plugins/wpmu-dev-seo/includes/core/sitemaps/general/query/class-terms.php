<?php
/**
 * Terms class for handling term queries in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Sitemaps\General\Queries;

use SmartCrawl\Settings;
use SmartCrawl\Singleton;
use SmartCrawl\Sitemaps\General\Item;
use SmartCrawl\Sitemaps\Query;

/**
 * Class Terms
 *
 * Handles the retrieval of term items for sitemaps.
 */
class Terms extends Query {

	use Singleton;

	/**
	 * Retrieves the items for the given type and page number.
	 *
	 * @param string $type The type of items to retrieve.
	 * @param int    $page_number The page number for pagination.
	 *
	 * @return array|Item[] The array of sitemap items.
	 */
	public function get_items( $type = '', $page_number = 0 ) {
		return $this->get_term_items(
			$type,
			$page_number,
			$this->get_include_ids( $type )
		);
	}

	/**
	 * Checks if the term is included in the sitemap.
	 *
	 * @param \WP_Term $term The term to check.
	 *
	 * @return bool True if the term is included, false otherwise.
	 */
	public function is_term_included( $term ) {
		if ( ! is_a( $term, '\WP_Term' ) ) {
			return false;
		}

		if ( ! in_array( $term->taxonomy, $this->get_supported_types(), true ) ) {
			return false;
		}

		$term_items = $this->get_term_items( $term->taxonomy, 0, array( $term->term_id ) );

		return ! empty( $term_items );
	}

	/**
	 * Retrieves the term items for the given type, page number, and include IDs.
	 *
	 * @param string $type The type of items to retrieve.
	 * @param int    $page_number The page number for pagination.
	 * @param array  $include_ids The IDs of terms to include.
	 *
	 * @return array|Item[] The array of term items.
	 */
	private function get_term_items( $type, $page_number, $include_ids = array() ) {
		if ( \smartcrawl_is_switch_active( '\SMARTCRAWL_SITEMAP_SKIP_TAXONOMIES' ) ) {
			return array();
		}

		$terms = $this->query_terms( $type, $page_number, $include_ids );
		$items = array();
		foreach ( $terms as $term_data ) {
			$term = new \WP_Term( $term_data );
			$url  = $this->get_term_url( $term );
			if ( \smartcrawl_get_term_meta( $term, $term->taxonomy, 'wds_noindex' ) ) {
				continue;
			}

			$item = new Item();
			$item->set_location( $url )
				->set_last_modified( $this->get_term_last_modified( $term ) );
			$items[] = $item;
		}

		return $items;
	}

	/**
	 * Queries the terms from the database.
	 *
	 * @param string $type The type of items to retrieve.
	 * @param int    $page_number The page number for pagination.
	 * @param array  $include_ids The IDs of terms to include.
	 *
	 * @return array|object|\stdClass[] The queried terms.
	 */
	private function query_terms( $type, $page_number, $include_ids ) {
		global $wpdb;

		$terms              = $wpdb->terms;
		$term_taxonomy      = $wpdb->term_taxonomy;
		$term_relationships = $wpdb->term_relationships;
		$posts              = $wpdb->posts;

		$included_types = empty( $type ) ? $this->get_supported_types() : array( $type );
		if ( empty( $included_types ) ) {
			return array();
		}
		$included_types_placeholders = $this->get_db_placeholders( $included_types );
		$included_types_string       = $wpdb->prepare( $included_types_placeholders, $included_types ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$types_where                 = "AND taxonomy IN ($included_types_string) ";

		$ignore_ids_where = '';
		$ignore_ids       = $this->get_ignored_ids( $included_types );
		if ( $ignore_ids ) {
			$ignore_ids_placeholders = $this->get_db_placeholders( $ignore_ids, '%d' );
			$ignore_ids_string       = $wpdb->prepare( $ignore_ids_placeholders, $ignore_ids ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$ignore_ids_where        = "AND $terms.term_id NOT IN ($ignore_ids_string)";
		}

		$include_ids_where = '';
		if ( $include_ids ) {
			$include_ids_placeholders = $this->get_db_placeholders( $include_ids, '%d' );
			$include_ids_string       = $wpdb->prepare( $include_ids_placeholders, $include_ids ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$include_ids_where        = "AND $terms.term_id IN ($include_ids_string)";
		}

		$limit  = $this->get_limit( $page_number );
		$offset = $this->get_offset( $page_number );

		$sub_query = "SELECT term_taxonomy_id, MAX(post_modified) AS last_modified FROM $term_relationships " .
			"INNER JOIN $posts ON object_id = ID " .
			'GROUP BY term_taxonomy_id';

		// TODO check if we need to sort on taxonomy before anything else so that terms of the same taxonomy are grouped together.
		$query = "SELECT $terms.term_id, name, slug, term_group, $term_taxonomy.term_taxonomy_id, taxonomy, description, parent, count, last_modified FROM $terms " .
			"INNER JOIN $term_taxonomy ON $terms.term_id = $term_taxonomy.term_id " .
			"LEFT OUTER JOIN ($sub_query) AS term_post_data ON term_post_data.term_taxonomy_id = $term_taxonomy.term_taxonomy_id " .
			'WHERE count > 0 ' .
			"$include_ids_where " .
			"$types_where " .
			"$ignore_ids_where" .
			'ORDER BY last_modified ASC, term_id ASC ' .
			"LIMIT $limit OFFSET $offset";

		$terms = $wpdb->get_results( $query ); // phpcs:ignore -- WordPress.DB.PreparedSQL.NotPrepared

		return $terms ? $terms : array();
	}

	/**
	 * Generates the database placeholders for the given items.
	 *
	 * @param array  $items The items to generate placeholders for.
	 * @param string $single_placeholder The placeholder format.
	 *
	 * @return string The generated placeholders.
	 */
	private function get_db_placeholders( $items, $single_placeholder = '%s' ) {
		return join( ',', array_fill( 0, count( $items ), $single_placeholder ) );
	}

	/**
	 * Retrieves the supported types.
	 *
	 * @return array The supported types.
	 */
	public function get_supported_types() {
		$smartcrawl_options = Settings::get_options();
		$candidates         = get_taxonomies(
			array(
				'public'  => true,
				'show_ui' => true,
			),
			'objects'
		);

		$sitemap_taxonomies = array();
		foreach ( $candidates as $taxonomy ) {
			if ( ! empty( $smartcrawl_options[ 'taxonomies-' . $taxonomy->name . '-not_in_sitemap' ] ) ) {
				continue;
			}
			$sitemap_taxonomies[] = $taxonomy->name;
		}

		return $sitemap_taxonomies;
	}

	/**
	 * Retrieves the filter prefix.
	 *
	 * @return string The filter prefix.
	 */
	public function get_filter_prefix() {
		return 'wds-sitemap-terms';
	}

	/**
	 * Retrieves the ignored term IDs for the given taxonomies.
	 *
	 * @param array $taxonomies The taxonomies to retrieve ignored IDs for.
	 *
	 * @return array The ignored term IDs.
	 */
	public function get_ignored_ids( $taxonomies ) {
		if ( ! is_array( $taxonomies ) ) {
			$taxonomies = array( $taxonomies );
		}

		$ids = array();
		foreach ( $taxonomies as $taxonomy ) {
			$ids = array_unique(
				array_merge(
					$ids,
					$this->get_ignored_url_ids( $taxonomy ),
					$this->get_ignored_canonical_url_ids( $taxonomy )
				)
			);
		}

		return $ids;
	}

	/**
	 * Retrieves the ignored term IDs based on URLs for the given taxonomy.
	 *
	 * @param string $taxonomy_name The name of the taxonomy.
	 *
	 * @return array The ignored term IDs.
	 */
	private function get_ignored_url_ids( $taxonomy_name ) {
		$ignore_urls = $this->get_absolute_ignore_urls();
		$term_ids    = array();
		foreach ( $ignore_urls as $ignore_url ) {
			$term_id = $this->get_term_id_from_url( $ignore_url, $taxonomy_name );
			if ( $term_id ) {
				$term_ids[] = $term_id;
			}
		}

		return $term_ids;
	}

	/**
	 * Retrieves the ignored canonical URL term IDs for the given taxonomy.
	 *
	 * @param string $taxonomy The name of the taxonomy.
	 *
	 * @return array The ignored canonical URL term IDs.
	 */
	private function get_ignored_canonical_url_ids( $taxonomy ) {
		$ignore_urls = $this->get_absolute_ignore_urls();
		$tax_meta    = get_option( 'wds_taxonomy_meta' );
		$term_ids    = array();
		if ( ! empty( $tax_meta[ $taxonomy ] ) && is_array( $tax_meta[ $taxonomy ] ) ) {
			$canonical_urls = array_map(
				'untrailingslashit',
				array_filter( array_column( $tax_meta[ $taxonomy ], 'wds_canonical' ) )
			);

			foreach ( $ignore_urls as $ignore_url ) {
				$term_id = array_search( $ignore_url, $canonical_urls, true );
				if ( $term_id ) {
					$term_ids[] = $term_id;
				}
			}
		}

		return $term_ids;
	}

	/**
	 * Retrieves the absolute ignore URLs.
	 *
	 * @return array The absolute ignore URLs.
	 */
	private function get_absolute_ignore_urls() {
		$ignore_urls = \SmartCrawl\Sitemaps\Utils::get_ignore_urls();
		if ( empty( $ignore_urls ) ) {
			return array();
		}

		return array_map( array( $this, 'absolute_url' ), $ignore_urls );
	}

	/**
	 * Converts a relative URL to an absolute URL.
	 *
	 * @param string $url The URL to convert.
	 *
	 * @return string The absolute URL.
	 */
	private function absolute_url( $url ) {
		$url = trim( $url );

		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( strpos( $url, $host ) === false ) {
			$url = home_url( $url );
		}

		return untrailingslashit( $url );
	}

	/**
	 * Retrieves the term ID from the given URL and taxonomy name.
	 *
	 * @param string $ignore_url The URL to check.
	 * @param string $taxonomy_name The name of the taxonomy.
	 *
	 * @return false|int The term ID or false if not found.
	 */
	private function get_term_id_from_url( $ignore_url, $taxonomy_name ) {
		$using_permalinks      = ! empty( get_option( 'permalink_structure' ) );
		$taxonomy              = get_taxonomy( $taxonomy_name );
		$taxonomy_rewrite_slug = ! empty( $taxonomy->rewrite['slug'] )
			? $taxonomy->rewrite['slug']
			: '';
		$slugs                 = array(
			'category' => 'cat',
			'post_tag' => 'tag',
		);
		$taxonomy_slug         = isset( $slugs[ $taxonomy_name ] )
			? $slugs[ $taxonomy_name ]
			: $taxonomy_name;

		if ( strpos( $ignore_url, "$taxonomy_slug=" ) !== false ) {
			$url_parts = wp_parse_url( $ignore_url );
			$query     = (string) \smartcrawl_get_array_value( $url_parts, 'query' );
			parse_str( $query, $query_vars );
			$identifier = \smartcrawl_get_array_value( $query_vars, $taxonomy_slug );
			if ( $identifier ) {
				$term = get_term_by( is_numeric( $identifier ) ? 'id' : 'slug', $identifier, $taxonomy_name );
				if ( $term ) {
					return $term->term_id;
				}
			}
		}

		if (
			$using_permalinks
			&&
			! empty( $taxonomy_rewrite_slug )
			&& strpos( $ignore_url, "/$taxonomy_rewrite_slug/" ) !== false
		) {
			$slugs_string = explode( "/$taxonomy_rewrite_slug/", $ignore_url )[1];
			$slugs        = explode( '/', untrailingslashit( $slugs_string ) );
			$term_slug    = array_pop( $slugs );
			$term         = get_term_by( 'slug', $term_slug, $taxonomy_name );
			if ( $term ) {
				return $term->term_id;
			}
		}

		return false;
	}

	/**
	 * Retrieves the term URL.
	 *
	 * @param \WP_Term $term The term to retrieve the URL for.
	 *
	 * @return string The term URL.
	 */
	private function get_term_url( $term ) {
		$canonical = \smartcrawl_get_term_meta( $term, $term->taxonomy, 'wds_canonical' );

		return $canonical ?: get_term_link( $term, $term->taxonomy );
	}

	/**
	 * Retrieves the last modified time for the given term.
	 *
	 * @param \WP_Term $term The term to retrieve the last modified time for.
	 *
	 * @return false|int The last modified time or false if not found.
	 */
	private function get_term_last_modified( $term ) {
		return empty( $term->last_modified )
			? time()
			: strtotime( $term->last_modified );
	}

	/**
	 * Retrieves the IDs of terms to include in the sitemap.
	 *
	 * @param string|array $types The types of terms to include.
	 *
	 * @return array The IDs of terms to include.
	 */
	private function get_include_ids( $types ) {
		$types   = empty( $types ) ? $this->get_supported_types() : array( $types );
		$include = apply_filters( 'wds_terms_sitemap_include_term_ids', array(), $types );

		return empty( $include ) || ! is_array( $include )
			? array()
			: array_filter( array_map( 'intval', $include ) );
	}
}