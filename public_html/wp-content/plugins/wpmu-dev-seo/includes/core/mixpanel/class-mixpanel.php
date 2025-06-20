<?php
/**
 * Class to handle mixpanel functionality.
 *
 * @since   3.7.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Mixpanel;

use SmartCrawl\Logger;
use SmartCrawl\Singleton;
use Smartcrawl_Vendor\Detection\MobileDetect;
use WPMUDEV_Analytics;

/**
 * Mixpanel main class.
 */
class Mixpanel {

	use Singleton;

	/**
	 * Mixpanel token for SmartCrawl
	 */
	const TOKEN = '5d545622e3a040aca63f2089b0e6cae7';

	/**
	 * Mixpanel instance.
	 *
	 * @var WPMUDEV_Analytics
	 */
	private $mixpanel = null;

	/**
	 * Mixpanel instance.
	 *
	 * @since 3.7.0
	 */
	protected function __construct() {
		if ( is_null( $this->mixpanel ) ) {
			if ( ! class_exists( 'WPMUDEV_Analytics' ) ) {
				require_once SMARTCRAWL_PLUGIN_DIR . 'external/wpmudev-analytics/autoload.php';
			}
			$extra_options  = array(
				'consumer' => 'socket',
			);
			$this->mixpanel = new WPMUDEV_Analytics( 'smartcrawl', 'SmartCrawl', 55, self::TOKEN, $extra_options );
			// Configure mixpanel.
			$this->mixpanel->identify( $this->identity() );
			$this->mixpanel->registerAll( $this->super_properties() );
		}
	}

	/**
	 * Get configured mixpanel instance.
	 *
	 * Use this method to make tracking events.
	 *
	 * @since 3.7.0
	 *
	 * @return WPMUDEV_Analytics
	 */
	public function tracker() {
		return $this->mixpanel;
	}

	/**
	 * Handle mixpanel error.
	 *
	 * @since 3.7.0
	 *
	 * @param string $code Error code.
	 * @param string $data Error data.
	 *
	 * @return void
	 */
	public function handle_error( $code, $data ) {
		Logger::error( "$code: $data" );
	}

	/**
	 * Get unique identity for current site.
	 *
	 * @since 3.7.0
	 *
	 * @return string
	 */
	private function identity() {
		$url = str_replace( array( 'http://', 'https://', 'www.' ), '', home_url() );

		return untrailingslashit( $url );
	}

	/**
	 * Get super properties for all events.
	 *
	 * These properties are attached to all events.
	 *
	 * @since 3.7.0
	 *
	 * @return array
	 */
	private function super_properties() {
		global $wpdb, $wp_version;

		$properties = array(
			'active_theme'       => get_stylesheet(),
			'locale'             => get_locale(),
			'mysql_version'      => $wpdb->get_var( 'SELECT VERSION()' ),
			'php_version'        => phpversion(),
			'plugin'             => 'SmartCrawl',
			'plugin_type'        => 'full' === \SMARTCRAWL_BUILD_TYPE ? 'Pro' : 'Free',
			'plugin_version'     => \SMARTCRAWL_VERSION,
			'server_type'        => $this->get_server_type(),
			'wp_type'            => is_multisite() ? 'multisite' : 'single',
			'wp_version'         => $wp_version,
			'device'             => $this->get_device_type(),
			'user_agent'         => isset( $_SERVER['HTTP_USER_AGENT'] ) ? wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			'memory_limit'       => ini_get( 'memory_limit' ),
			'max_execution_time' => ini_get( 'max_execution_time' ),
		);

		/**
		 * Filter hook to modify super properties.
		 *
		 * @since 3.7.0
		 *
		 * @param array $properties Properties.
		 */
		return apply_filters( 'smartcrawl_mixpanel_super_properties', $properties );
	}

	/**
	 * Get current server type name.
	 *
	 * Only apache and ngnix can be detected.
	 *
	 * @since 3.7.0
	 *
	 * @return string
	 */
	private function get_server_type() {
		if ( empty( $_SERVER['SERVER_SOFTWARE'] ) ) {
			return '';
		}

		$server_software = wp_unslash( $_SERVER['SERVER_SOFTWARE'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! is_array( $server_software ) ) {
			$server_software = array( $server_software );
		}

		$server_software = array_map( 'strtolower', $server_software );

		if ( $this->array_has_needle( $server_software, 'nginx' ) ) {
			return 'nginx';
		}

		if ( $this->array_has_needle( $server_software, 'apache' ) ) {
			return 'apache';
		}

		return '';
	}

	/**
	 * Get current device type.
	 *
	 * @since 3.7.0
	 *
	 * @return string
	 */
	private function get_device_type() {
		$detector = new MobileDetect();

		return ( $detector->isMobile() ? ( $detector->isTablet() ? 'Tablet' : 'Mobile' ) : 'Desktop' );
	}

	/**
	 * Check if array of strings has a string.
	 *
	 * @since 3.7.0
	 *
	 * @param array  $haystack Array of strings.
	 * @param string $needle   Value to search.
	 *
	 * @return bool
	 */
	private function array_has_needle( $haystack, $needle ) {
		foreach ( $haystack as $item ) {
			if ( strpos( $item, $needle ) !== false ) {
				return true;
			}
		}

		return false;
	}
}