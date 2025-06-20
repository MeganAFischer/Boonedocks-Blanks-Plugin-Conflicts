<?php
/**
 * Sitemap class for handling sitemap-related services in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Sitemaps;

use SmartCrawl\Admin\Settings\Admin_Settings;
use SmartCrawl\Settings;

/**
 * Abstract class Sitemap
 *
 * Provides a base for all sitemap classes.
 */
abstract class Sitemap {
	const SITEMAP_TYPE_INDEX = 'index';

	/**
	 * Adds rewrite rules for the sitemap.
	 *
	 * @return mixed
	 */
	abstract public function add_rewrites();

	/**
	 * Checks if the request can be handled by the sitemap.
	 *
	 * @return mixed
	 */
	abstract public function can_handle_request();

	/**
	 * Performs a fallback action if the request cannot be handled.
	 *
	 * @return mixed
	 */
	abstract public function do_fallback();

	/**
	 * Serves the sitemap.
	 *
	 * @return mixed
	 */
	abstract public function serve();

	/**
	 * Checks if the sitemap is enabled.
	 *
	 * @return bool True if the sitemap is enabled, false otherwise.
	 */
	public function is_enabled() {
		return Settings::get_setting( 'sitemap' )
			&& Admin_Settings::is_tab_allowed( Settings::TAB_SITEMAP );
	}

	/**
	 * Outputs the XML content.
	 *
	 * @param string $xml  The XML content to output.
	 * @param bool   $gzip Whether to gzip the output.
	 *
	 * @return void
	 */
	protected function output_xml( $xml, $gzip ) {
		if ( ! headers_sent() ) {
			status_header( 200 );
			// Prevent the search engines from indexing the XML Sitemap.
			header( 'X-Robots-Tag: noindex, follow' );
			header( 'Content-Type: text/xml; charset=UTF-8' );

			if (
				$this->is_gzip_supported()
				&& function_exists( 'gzencode' )
				&& $gzip
			) {
				header( 'Content-Encoding: gzip' );
				$xml = gzencode( $xml );
			}
			die( $xml ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Checks if gzip is supported.
	 *
	 * @return bool True if gzip is supported, false otherwise.
	 */
	private function is_gzip_supported() {
		$accepted = (string) \smartcrawl_get_array_value( $_SERVER, 'HTTP_ACCEPT_ENCODING' );

		return stripos( $accepted, 'gzip' ) !== false;
	}

	/**
	 * Performs a 404 action.
	 *
	 * @return void
	 */
	protected function do_404() {
		global $wp_query;

		$wp_query->set_404();
		status_header( 404 );
	}
}