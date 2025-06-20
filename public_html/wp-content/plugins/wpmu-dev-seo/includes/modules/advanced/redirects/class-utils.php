<?php
/**
 * Utility class for Redirection.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Modules\Advanced\Redirects;

use SmartCrawl\Settings;
use SmartCrawl\Singleton;
use SmartCrawl\String_Utils;

/**
 * Utility class for Redirection.
 */
class Utils {

	use Singleton;

	const DEFAULT_TYPE = 302;

	/**
	 * Default redirection type.
	 *
	 * @return int
	 */
	public function get_default_type() {
		$default_type = \smartcrawl_get_array_value( get_option( Settings::ADVANCED_MODULE ), 'default_type' );

		return empty( $default_type )
			? self::DEFAULT_TYPE
			: $default_type;
	}

	/**
	 * Retrieves redirects types with no actual redirect required.
	 *
	 * For some redirect types, we don't need a destination.
	 * We set the header status to these types.
	 *
	 * @return int[]
	 */
	public function get_non_redirect_types() {
		return array( 410, 451 );
	}

	/**
	 * Checks if a redirect type is non redirect type.
	 *
	 * @param string|int $type Redirect type.
	 *
	 * @return bool
	 */
	public function is_non_redirect_type( $type ) {
		return in_array( intval( $type ), $this->get_non_redirect_types(), true );
	}

	/**
	 * Create redirect item.
	 *
	 * @todo: format rules within this function.
	 *
	 * @param string         $source Source.
	 * @param array | string $destination Destination.
	 * @param string         $type Type.
	 * @param string         $title Label to identity long or similar URLs.
	 * @param array          $options Options.
	 * @param array          $rules Rules.
	 *
	 * @return Item
	 */
	public function create_redirect_item( $source, $destination, $type = null, $title = '', $options = array(), $rules = array() ) {
		$redirect_item = ( new Item() )
			->set_type( $this->prepare_type( $type ) )
			->set_destination( $destination )
			->set_title( \smartcrawl_clean( $title ) )
			->set_options( $this->prepare_array_field( $options ) )
			->set_rules( $rules );

		if ( $redirect_item->is_regex() ) {
			$redirect_item
				->set_source( $source )
				->set_path( 'regex' );
		} else {
			$source_normalized = $this->prepare_source( $source );
			$path_normalized   = $this->source_to_path( $source_normalized );
			$redirect_item
				->set_source( $source_normalized )
				->set_path( $path_normalized );
		}

		return $redirect_item;
	}

	/**
	 * Prepare source.
	 *
	 * @param string $source Source.
	 *
	 * @return string
	 */
	private function prepare_source( $source ) {
		return $this->prepare_url( $source );
	}

	/**
	 * Prepare type.
	 *
	 * @param string $type Type.
	 *
	 * @return int
	 */
	private function prepare_type( $type ) {
		$default_type = $this->get_default_type();
		$type         = empty( $type )
			? $default_type
			: $type;

		return intval( $type );
	}

	/**
	 * Prepare options.
	 *
	 * @param array $options Options.
	 *
	 * @return array
	 */
	private function prepare_array_field( $options ) {
		return empty( $options ) || ! is_array( $options )
			? array()
			: \smartcrawl_clean( $options );
	}

	/**
	 * Remove scheme from url.
	 *
	 * @param string $url Url.
	 *
	 * @return string
	 */
	private function remove_scheme( $url ) {
		return str_replace( array( 'http://', 'https://' ), '', $url );
	}

	/**
	 * Generate path from source.
	 *
	 * @param string $source Source.
	 *
	 * @return string
	 */
	public function source_to_path( $source ) {
		$path     = $this->remove_scheme( $source );
		$home_url = $this->remove_scheme( $this->get_unfiltered_home_url( '/' ) );

		// Normalize 'www.' prefix only once.
		$path     = ltrim( preg_replace( '/^www\./', '', $path ), '/' );
		$home_url = ltrim( preg_replace( '/^www\./', '', $home_url ), '/' );

		if ( strpos( $path, $home_url ) === 0 ) {
			$path = str_replace( $home_url, '/', $path );
		}

		return $this->normalize_path( $path );
	}

	/**
	 * Normalize path.
	 *
	 * @param string $path Path.
	 *
	 * @return string
	 */
	public function normalize_path( $path ) {
		// No slash at the end.
		$path = untrailingslashit( $path );

		// Normalize case.
		$path = String_Utils::lowercase( $path );

		// Encode characters.
		$path = $this->encode_path( $path );

		// Always start with a slash.
		return $this->enforce_starting_slash( $path );
	}

	/**
	 * Encode path.
	 *
	 * @param string $path Path.
	 *
	 * @return string
	 */
	private function encode_path( $path ) {
		$decode = array(
			'/',
			':',
			'[',
			']',
			'@',
			'~',
			',',
			'(',
			')',
			';',
			'?',
		);

		// Below is just a trick to fix double encoding issue in URL handling.
		$path = rawurldecode( $path );

		// URL encode everything - this converts any i10n to the proper encoding.
		$path = rawurlencode( $path );

		// We also converted things we don't want encoding, such as a /. Change these back.
		foreach ( $decode as $char ) {
			$path = str_replace( rawurlencode( $char ), $char, $path );
		}

		return $path;
	}

	/**
	 * Prepare destination from destination url.
	 *
	 * @param string $destination Destination url.
	 *
	 * @return string
	 */
	private function prepare_destination( $destination ) {
		return $destination ? $this->prepare_url( $destination ) : '';
	}

	/**
	 * Add starting slash to string.
	 *
	 * @param string $str_val String.
	 *
	 * @return string
	 */
	private function enforce_starting_slash( $str_val ) {
		return '/' . ltrim( $str_val, '/' );
	}

	/**
	 * Make sure url to be absolute url or starting with slash.
	 *
	 * @param string $url Url.
	 *
	 * @return string
	 */
	private function prepare_url( $url ) {
		// Trim.
		$url = trim( $url );
		// Remove new lines.
		$url = preg_replace( "/[\r\n\t].*?$/s", '', $url );
		// Remove control codes.
		$url = preg_replace( '/[^\PC\s]/u', '', $url );
		// Escape.
		$url = esc_url_raw( $url );

		return $this->is_url_absolute( $url )
			? $url
			: $this->enforce_starting_slash( $url );
	}

	/**
	 * Check if it's absolute url.
	 *
	 * @param string $url Url.
	 *
	 * @return bool
	 */
	private function is_url_absolute( $url ) {
		return strpos( $url, 'http://' ) === 0 || strpos( $url, 'https://' ) === 0;
	}

	/**
	 * Retrieves full url including host.
	 *
	 * @param string $url Url.
	 *
	 * @return string
	 */
	public function get_full_url( $url ) {
		if ( ! empty( $url['id'] ) ) {
			$url = get_permalink( $url['id'] );
		}

		if ( '/' === $url[0] ) {
			$url = get_site_url() . $url;
		}

		return rtrim( $url, '/' );
	}

	/**
	 * Retrieves the URL for the current site where the front end is accessible.
	 *
	 * This is an alternative method for WP core home_url() function to skip all
	 * filters from home_url.
	 *
	 * @since 3.8.2
	 *
	 * @param string $path Optional. Path relative to the home URL. Default empty.
	 *
	 * @return string Home URL link with optional path appended.
	 */
	public function get_unfiltered_home_url( $path = '' ) {
		$url = get_option( 'home' );

		$scheme = is_ssl() ? 'https' : wp_parse_url( $url, PHP_URL_SCHEME );

		$url = set_url_scheme( $url, $scheme );

		if ( $path && is_string( $path ) ) {
			$url .= '/' . ltrim( $path, '/' );
		}

		/**
		 * Filters the unfiltered home URL.
		 *
		 * @since 3.8.2
		 *
		 * @param string      $url         The complete home URL including scheme and path.
		 * @param string      $path        Path relative to the home URL. Blank string if no path is specified.
		 * @param string|null $orig_scheme Scheme to give the home URL context. Accepts 'http', 'https',
		 *                                 'relative', 'rest', or null.
		 */
		return apply_filters( 'smartcrawl_unfiltered_home_url', $url, $path );
	}
}