<?php
/**
 * Cache class for handling sitemap caching in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Sitemaps;

use SmartCrawl\Logger;
use SmartCrawl\Singleton;

/**
 * Class Cache
 *
 * Handles the caching of sitemaps.
 */
class Cache {
	const CACHE_FILE_NAME_FORMAT = '%s-sitemap%d.xml';
	const CACHE_PRISTINE_OPTION  = 'wds_sitemap_cache_pristine';

	use Singleton;

	/**
	 * Sets the cached sitemap.
	 *
	 * @param string $type The type of the sitemap.
	 * @param int    $page The page number of the sitemap.
	 * @param string $sitemap The sitemap content.
	 *
	 * @return bool True if the sitemap was cached successfully, false otherwise.
	 */
	public function set_cached( $type, $page, $sitemap ) {
		return $this->write_to_cache_file(
			$this->cache_file_name( $type, $page ),
			$sitemap
		);
	}

	/**
	 * Retrieves the cached sitemap.
	 *
	 * @param string $type The type of the sitemap.
	 * @param int    $page The page number of the sitemap.
	 *
	 * @return string|false The cached sitemap content or false if not found.
	 */
	public function get_cached( $type, $page ) {
		if ( $this->is_cache_pristine() ) {
			return $this->get_from_cache_file( $this->cache_file_name( $type, $page ) );
		}

		$this->drop_cache();
		return false;
	}

	/**
	 * Drops the sitemap cache.
	 *
	 * @return bool True if the cache was dropped successfully, false otherwise.
	 */
	public function drop_cache() {
		$file_system = $this->fs_direct();
		$cache_dir   = $this->get_cache_dir();
		if ( empty( $cache_dir ) ) {
			Logger::error( 'Sitemap cache could not be dropped because it does not exist' );
			return false;
		}

		$removed = $file_system->rmdir( $cache_dir, true );
		if ( ! $removed ) {
			Logger::error( 'Sitemap cache directory could not be removed' );
			return false;
		}

		$this->set_cache_pristine( true ); // An empty cache is a pristine cache.
		Logger::info( 'Sitemap cache dropped' );
		return true;
	}

	/**
	 * Retrieves the filesystem direct instance.
	 *
	 * @return \WP_Filesystem_Direct The filesystem direct instance.
	 */
	private function fs_direct() {
		if ( ! class_exists( '\WP_Filesystem_Direct', false ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
		}
		return new \WP_Filesystem_Direct( null );
	}

	/**
	 * Generates the cache file name.
	 *
	 * @param string $type The type of the sitemap.
	 * @param int    $page The page number of the sitemap.
	 *
	 * @return string The generated cache file name.
	 */
	private function cache_file_name( $type, $page ) {
		$file_name = sprintf( self::CACHE_FILE_NAME_FORMAT, $type, $page );

		return apply_filters( 'wds_sitemap_cache_file_name', $file_name, $type, $page );
	}

	/**
	 * Writes content to a cache file.
	 *
	 * @param string $filename The name of the cache file.
	 * @param string $contents The content to write to the cache file.
	 *
	 * @return bool True if the content was written successfully, false otherwise.
	 */
	private function write_to_cache_file( $filename, $contents ) {
		$path = $this->get_cache_dir( $filename );
		if (
			empty( $path )
			|| ! \smartcrawl_file_put_contents( $path, $contents, LOCK_EX )
		) {
			Logger::error( "Failed writing sitemap cache file to [$path]" );
			return false;
		}

		Logger::info( "Added file to sitemap cache: [$path]" );
		return true;
	}

	/**
	 * Get content from a cache file.
	 *
	 * @param string $filename The name of the cache file.
	 *
	 * @return string|false The content of the cache file or false if not found.
	 */
	private function get_from_cache_file( $filename ) {
		$path = $this->get_cache_dir( $filename );

		if ( ! empty( $path ) && file_exists( $path ) ) {
			Logger::info( "Sitemap file read from cache: [$path]" );
			return \smartcrawl_file_get_contents( $path );
		}

		Logger::info( "Sitemap file not found in cache: [$path]" );
		return false;
	}

	/**
	 * Retrieves the cache directory.
	 *
	 * @param string $postfix The postfix to append to the cache directory.
	 *
	 * @return false|string The cache directory path or false if it could not be created.
	 */
	public function get_cache_dir( $postfix = '' ) {
		$path = \smartcrawl_uploads_dir();
		$path = "{$path}sitemap/";

		// Attempt to create the dir in case it doesn't already exist.
		$dir_exists = wp_mkdir_p( $path );
		if ( ! $dir_exists ) {
			Logger::error( "Sitemap cache directory could not be created at [$path]" );
			return false;
		}

		return "$path$postfix";
	}

	/**
	 * Checks if the cache is pristine.
	 *
	 * @return bool True if the cache is pristine, false otherwise.
	 */
	public function is_cache_pristine() {
		return in_array(
			get_current_blog_id(),
			$this->get_sitemap_pristine_option(),
			true
		);
	}

	/**
	 * Invalidates the cache.
	 *
	 * @return void
	 */
	public function invalidate() {
		$this->set_cache_pristine( false );
	}

	/**
	 * Sets the cache pristine option.
	 *
	 * @param bool $value The value to set for the cache pristine option.
	 *
	 * @return void
	 */
	private function set_cache_pristine( $value ) {
		$pristine        = $this->get_sitemap_pristine_option();
		$current_site_id = get_current_blog_id();

		if ( $value ) {
			if ( ! in_array( $current_site_id, $pristine, true ) ) {
				$pristine[] = $current_site_id;
				$this->update_sitemap_pristine_option( $pristine );
			}
		} elseif ( ! is_multisite() ) {
				// The single site is out of date now so drop everything.
				$this->delete_sitemap_pristine_option();
		} else {
			$this->update_sitemap_pristine_option(
				array_diff( $pristine, array( $current_site_id ) )
			);
		}
	}

	/**
	 * Retrieves the sitemap pristine option.
	 *
	 * @return array The sitemap pristine option.
	 */
	private function get_sitemap_pristine_option() {
		$value = get_site_option( self::CACHE_PRISTINE_OPTION, array() );
		return is_array( $value )
			? $value
			: array();
	}

	/**
	 * Updates the sitemap pristine option.
	 *
	 * @param array $value The value to update the sitemap pristine option with.
	 *
	 * @return bool True if the option was updated successfully, false otherwise.
	 */
	private function update_sitemap_pristine_option( $value ) {
		return update_site_option( self::CACHE_PRISTINE_OPTION, $value );
	}

	/**
	 * Deletes the sitemap pristine option.
	 *
	 * @return bool True if the option was deleted successfully, false otherwise.
	 */
	private function delete_sitemap_pristine_option() {
		return delete_site_option( self::CACHE_PRISTINE_OPTION );
	}

	/**
	 * Checks if the cache directory is writable.
	 *
	 * @return bool True if the cache directory is writable, false otherwise.
	 */
	public function is_writable() {
		return is_writeable( $this->get_cache_dir() );
	}

	/**
	 * Checks if the index sitemap is cached.
	 *
	 * @return bool True if the index sitemap is cached, false otherwise.
	 */
	public function is_index_cached() {
		if ( ! $this->is_cache_pristine() ) {
			// If cache is not pristine, we don't care if the file exists or not.
			return false;
		}

		$file_name = $this->cache_file_name( Front::SITEMAP_TYPE_INDEX, 0 );
		$path      = $this->get_cache_dir( $file_name );

		return ! empty( $path ) && file_exists( $path );
	}
}