<?php
/**
 * Object cache module.
 *
 * Use object cache with ability to clear a whole group once.
 *
 * @link    http://wpmudev.com
 * @since   3.3.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Cache;

use SmartCrawl\Singleton;

/**
 * Class Object_Cache
 *
 * @since   3.3.0
 * @package SmartCrawl
 */
class Object_Cache {

	use Singleton;

	/**
	 * Default cache group name.
	 *
	 * @since 3.3.0
	 */
	const GROUP = 'wds-cache';

	/**
	 * Default cache expiry time.
	 *
	 * @since 3.3.0
	 */
	const EXPIRY = 86400;

	/**
	 * Get data from the object cache.
	 *
	 * Use this so that we can purge cache by group.
	 *
	 * @since 3.3.0
	 *
	 * @param string $key     Cache key.
	 * @param string $group   Cache group.
	 * @param mixed  $default Default value.
	 *
	 * @return mixed
	 */
	public function get_cache( $key, $group = '', $default = null ) {
		// Get cache group version.
		$version = $this->get_version( $group );

		if ( ! empty( $version ) ) {
			// Get cache data.
			$data = wp_cache_get( $key, $this->get_group( $group ) );

			// Return only data.
			if ( isset( $data['version'] ) && (int) $version === (int) $data['version'] && isset( $data['data'] ) ) {
				return $data['data'];
			}
		}

		return $default;
	}

	/**
	 * Set data to the object cache.
	 *
	 * Use this so that we can purge cache by group.
	 *
	 * @param string $key    Cache key.
	 * @param mixed  $data   Data to store.
	 * @param string $group  Cache group.
	 * @param int    $expiry Expiration time (24 hours by default).
	 *
	 * @return void
	 */
	public function set_cache( $key, $data, $group = '', $expiry = self::EXPIRY ) {
		if ( ! empty( $data ) ) {
			$version = $this->get_version( $group );

			// If version not found set now.
			if ( empty( $version ) ) {
				$this->set_version( $group );
			}

			// Setup cache data.
			$data = array(
				'version' => empty( $version ) ? 1 : $version,
				'data'    => $data,
			);

			// Expiry is necessary to avoid over loading memory.
			$expiry = empty( $expiry ) ? self::EXPIRY : (int) $expiry;

			// Set cache.
			wp_cache_set( $key, $data, $this->get_group( $group ), $expiry );
		}
	}

	/**
	 * Delete an item from object cache.
	 *
	 * @since 3.3.0
	 *
	 * @param string $key   Cache key.
	 * @param string $group Cache group.
	 *
	 * @return bool
	 */
	public function purge_cache( $key, $group = self::GROUP ) {
		return wp_cache_delete( $key, $this->get_group( $group ) );
	}

	/**
	 * Delete entire group from object cache.
	 *
	 * Group purging will be done by incrementing the version of the
	 * cache group. By incrementing old data will be invalidated.
	 *
	 * @since 3.3.0
	 *
	 * @param string $group Cache group.
	 *
	 * @return bool
	 */
	public function purge_cache_group( $group ) {
		return $this->increment_version( $group );
	}

	/**
	 * Refresh a cache group.
	 *
	 * Refreshing will be done by incrementing the version of the
	 * cache group. By incrementing old data will be invalidated.
	 * We are NOT using wp_cache_incr() to set new version if not
	 * already exist.
	 *
	 * @since 3.3.0
	 *
	 * @param string $group Cache group.
	 *
	 * @return bool
	 */
	private function increment_version( $group ) {
		// Get cache group version.
		$version = $this->get_version( $group );

		// Increment version.
		$version = ( (int) $version ) + 1;

		// Update with new version.
		return $this->set_version( $group, $version );
	}

	/**
	 * Get the cache group version.
	 *
	 * @since 3.3.0
	 *
	 * @param string $group Cache group key.
	 *
	 * @return int
	 */
	private function get_version( $group ) {
		$version = wp_cache_get( $this->get_group( $group ) . '-version', self::GROUP );

		return empty( $version ) ? 0 : (int) $version;
	}

	/**
	 * Set the cache group version.
	 *
	 * @since 3.3.0
	 *
	 * @param string $group   Cache group key.
	 * @param int    $version Version.
	 *
	 * @return bool
	 */
	private function set_version( $group, $version = 1 ) {
		$version = empty( $version ) ? 1 : (int) $version;

		return wp_cache_set( $this->get_group( $group ) . '-version', $version, self::GROUP );
	}

	/**
	 * Get the group name for cache.
	 *
	 * @param string $group Cache group.
	 *
	 * @return string
	 */
	private function get_group( $group = self::GROUP ) {
		return empty( $group ) ? self::GROUP : $group;
	}
}