<?php
/**
 * Singleton class for caching terms.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Cache;

use SmartCrawl\Singleton;
use SmartCrawl\Entities\Taxonomy_Term;

/**
 * Term_Cache Class
 */
class Term_Cache {

	use Singleton;

	/**
	 * Stores cached data.
	 *
	 * @var array $cache
	 */
	private $cache = array();

	/**
	 * Get the taxonomy term by term ID.
	 *
	 * @param int $term_id The ID of the taxonomy term.
	 *
	 * @return Taxonomy_Term|null The taxonomy term object if found, null otherwise.
	 */
	public function get_term( $term_id ) {
		if ( empty( $this->cache[ $term_id ] ) ) {
			$term = new Taxonomy_Term( $term_id );
			if ( ! $term->get_wp_term() ) {
				return null;
			}
			$this->cache[ $term_id ] = $term;
		}

		return $this->cache[ $term_id ];
	}

	/**
	 * Clears the cache for a specific term.
	 *
	 * @param int $term_id The ID of the term to purge the cache for.
	 *
	 * @return void
	 */
	public function purge( $term_id ) {
		unset( $this->cache[ $term_id ] );
	}

	/**
	 * Purges all data from the cache.
	 *
	 * @return void
	 */
	public function purge_all() {
		$this->cache = array();
	}
}