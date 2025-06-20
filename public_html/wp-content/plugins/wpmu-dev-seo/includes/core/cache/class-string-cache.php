<?php
/**
 * Singleton class for caching string.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Cache;

use SmartCrawl\Singleton;
use SmartCrawl\SmartCrawl_String;

/**
 * String_Cache class.
 */
class String_Cache {

	use Singleton;

	/**
	 * Stores cached data.
	 *
	 * @var array $cache
	 */
	private $cache = array();

	/**
	 * Retrieves a string from the cache or creates a new cache entry if it doesn't exist.
	 *
	 * @param string $string The string to retrieve or create a cache entry for.
	 * @param string $language The language of the string.
	 *
	 * @return SmartCrawl_String The cached string.
	 */
	public function get_string( $string, $language ) {
		$key = $this->make_key( $string, $language );
		if ( empty( $this->cache[ $key ] ) ) {
			$this->cache[ $key ] = new SmartCrawl_String( $string, $language );
		}

		return $this->cache[ $key ];
	}

	/**
	 * Removes a string from the cache.
	 *
	 * @param string $string The string to remove from the cache.
	 * @param string $language The language of the string.
	 *
	 * @return void
	 */
	public function purge( $string, $language ) {
		$key = $this->make_key( $string, $language );

		unset( $this->cache[ $key ] );
	}

	/**
	 * Clears the cache by resetting the cache property to an empty array.
	 */
	public function purge_all() {
		$this->cache = array();
	}

	/**
	 * Generates a key using the provided string and language.
	 *
	 * @param string $string The string to generate the key from.
	 * @param string $language The language used to generate the key.
	 *
	 * @return string The generated key
	 */
	private function make_key( $string, $language ) {
		return md5( "$string-$language" );
	}
}