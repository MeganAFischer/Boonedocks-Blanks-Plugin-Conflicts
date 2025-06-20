<?php
/**
 * Class to manage MaxMind GeoLite2-Country Database.
 *
 * @since   3.8.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Integration\Maxmind;

use SmartCrawl\Logger;
use SmartCrawl\Services\Service;
use SmartCrawl\Settings;
use SmartCrawl\Singleton;
use Smartcrawl_Vendor\MaxMind\Db\Reader;

/**
 * MaxMind GeoLite2-Country Database class.
 */
class GeoDB {

	use Singleton;

	/**
	 * MaxMind database name.
	 */
	public const DB_NAME = 'GeoLite2-Country';

	/**
	 * MaxMind database extension.
	 */
	public const DB_EXT = '.mmdb';

	/**
	 * MaxMind directory name.
	 */
	public const DB_DIRECTORY = 'maxmind';

	/**
	 * Retrieves database's full name.
	 *
	 * @return string
	 */
	public function get_db_full_name() {
		return self::DB_NAME . self::DB_EXT;
	}

	/**
	 * Retrieves database base path.
	 *
	 * @since 3.8.0
	 *
	 * @return string|false
	 */
	public function get_db_dir() {
		$path = \smartcrawl_uploads_dir();
		$path = $path . 'maxmind';

		// Attempts to create the dir in case it doesn't already exist.
		$dir_exists = wp_mkdir_p( $path );

		if ( ! $dir_exists ) {
			Logger::error( "MaxMind database directory could not be created at [$path]" );
			return false;
		}

		return $path;
	}

	/**
	 * Downloads a URL to a local temporary file using the WordPress HTTP API.
	 *
	 * @param string $license_key License key.
	 *
	 * @return bool|string|\WP_Error
	 */
	public function download_url( $license_key ) {
		$url = add_query_arg(
			array(
				'edition_id'  => self::DB_NAME,
				'license_key' => rawurlencode( sanitize_text_field( $license_key ) ),
				'suffix'      => 'tar.gz',
			),
			'https://download.maxmind.com/app/geoip_download'
		);

		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		return download_url( $url );
	}

	/**
	 * Extracts downloaded database.
	 *
	 * @param string $temp_path Temp path.
	 *
	 * @since 3.8.0
	 *
	 * @return string|\WP_Error Path to the database file or an error.
	 */
	public function extract_db( $temp_path ) {
		try {
			self::delete_db();

			$db_dir = $this->get_db_dir();

			if ( ! $db_dir ) {
				return new \WP_Error( 'wds_maxmind_db_dir_error', 'Failed to create maxmind directory' );
			}

			$phar = new \PharData( $temp_path );
			$phar->extractTo( $db_dir, null, true );

			$db_path = $db_dir . DIRECTORY_SEPARATOR . $phar->current()->getFileName() . DIRECTORY_SEPARATOR . $this->get_db_full_name();

			Settings::update_specific_options( 'wds-maxmind-geodb', $db_path );
		} catch ( \Exception $exception ) {
			return new \WP_Error( 'wds_maxmind_extract_db_error', $exception->getMessage() );
		} finally {
			// Archive file is not needed.
			wp_delete_file( $temp_path );
		}

		return $db_path;
	}

	/**
	 * Deletes database.
	 *
	 * @since 3.8.0
	 */
	public function delete_db() {
		$db_path = Settings::get_specific_options( 'wds-maxmind-geodb', false );

		if ( ! $db_path ) {
			return;
		}

		// Easily interacts with the filesystem.
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		global $wp_filesystem;

		if ( $wp_filesystem->exists( $db_path ) ) {
			$wp_filesystem->delete( $db_path, true );
		}

		Settings::delete_specific_options( 'wds-maxmind-geodb' );
	}

	/**
	 * Activates license key and returns symbolized key if successful.
	 *
	 * @param string $license_key License key to be used for activation.
	 *
	 * @return string|\WP_Error
	 */
	public function activate_license( $license_key ) {
		$tmp = self::get()->download_url( $license_key );

		if ( is_wp_error( $tmp ) ) {
			Logger::error( 'Error from MaxMind: ' . $tmp->get_error_message() );

			return $tmp;
		}

		Settings::update_specific_options( 'wds-maxmind-license-key', $license_key );

		$db_path = self::get()->extract_db( $tmp );

		return substr( $license_key, 0, 7 ) . str_repeat( '*', 10 );
	}

	/**
	 * Retrieves symbolized license key or empty string it not existing.
	 *
	 * @param bool $encrypted Wheter the license should be entrypted or not.
	 *
	 * @return string
	 */
	public function get_license( $encrypted = true ) {
		if ( ! Service::get( Service::SERVICE_SITE )->is_member() ) {
			return '';
		}

		$license_key = Settings::get_specific_options( 'wds-maxmind-license-key', false );

		if ( ! $license_key ) {
			return '';
		}

		$db_path = Settings::get_specific_options( 'wds-maxmind-geodb', false );

		if ( ! $db_path ) {
			return '';
		}

		return $encrypted ? substr( $license_key, 0, 7 ) . str_repeat( '*', 10 ) : $license_key;
	}

	/**
	 * Deletes activated license key.
	 *
	 * @since 3.8.0
	 */
	public function delete_license() {
		Settings::delete_specific_options( 'wds-maxmind-license-key' );
	}

	/**
	 * Retrieves country code by IP.
	 *
	 * @return string|false
	 */
	public function get_country_by_ip() {
		if ( ! self::get_license() ) {
			return false;
		}

		$db_path = Settings::get_specific_options( 'wds-maxmind-geodb', false );

		$reader = new Reader( $db_path );

		$data = $reader->get( self::get_ip() );

		if ( empty( $data ) || ! isset( $data['country'] ) ) {
			return false;
		}

		return $data['country']['iso_code'];
	}

	/**
	 * Retrieves IP address.
	 *
	 * @return string
	 */
	public function get_ip() {
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			// IP is from the share internet.
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			// IP is from the proxy.
			$ip_list = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
			$ip      = trim( $ip_list[0] ); // Take the first valid IP.
		} else {
			// IP is from the remote address.
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		// phpcs:enable

		return $ip;
	}
}