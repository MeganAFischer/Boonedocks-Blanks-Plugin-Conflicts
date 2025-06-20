<?php
/**
 * This file contains the Front class for handling the front-end sitemap functionality.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Sitemaps;

use SmartCrawl\Singleton;
use SmartCrawl\Controllers;

/**
 * Class Front
 *
 * Handles the front-end sitemap functionality.
 */
class Front extends Controllers\Controller {

	use Singleton;

	const SITEMAP_TYPE_INDEX            = 'index';
	const SITEMAP_REWRITE_RULES_FLUSHED = 'wds-sitemap-rewrite-rules-flushed';

	/**
	 * An array of Sitemap objects for handling different types of sitemaps.
	 *
	 * @var Sitemap[]
	 */
	private $sitemaps;

	/**
	 * Constructor.
	 *
	 * Initializes the sitemaps array with News and General sitemaps.
	 */
	public function __construct() {
		parent::__construct();

		$this->sitemaps = array(
			new \SmartCrawl\Sitemaps\News\Sitemap(),
			new \SmartCrawl\Sitemaps\General\Sitemap(),
		);
	}

	/**
	 * Initialize the class.
	 *
	 * @return bool True on success, false on failure.
	 */
	protected function init() {
		add_action( 'init', array( $this, 'add_rewrites' ) );
		add_action( 'wp', array( $this, 'serve_sitemaps' ), 999 );
		add_action( 'wp_sitemaps_enabled', array( $this, 'maybe_disable_native_sitemap' ) );

		return true;
	}

	/**
	 * Maybe disable the native sitemap.
	 *
	 * @param bool $is_enabled Whether the native sitemap is enabled.
	 *
	 * @return bool False if the native sitemap should be disabled, otherwise the original value.
	 */
	public function maybe_disable_native_sitemap( $is_enabled ) {
		if ( Utils::override_native() ) {
			return false;
		}

		return $is_enabled;
	}

	/**
	 * Add rewrite rules for the sitemaps.
	 *
	 * @return void
	 */
	public function add_rewrites() {
		$this->add_styling_rewrites();

		foreach ( $this->sitemaps as $sitemap ) {
			$sitemap->add_rewrites();
		}

		$this->maybe_flush_rewrite_rules();
	}

	/**
	 * Serve the sitemaps.
	 *
	 * @return void
	 */
	public function serve_sitemaps() {
		$this->maybe_serve_xsl_stylesheet();

		foreach ( $this->sitemaps as $sitemap ) {
			$this->serve_sitemap( $sitemap );
		}
	}

	/**
	 * Serve a specific sitemap.
	 *
	 * @param Sitemap $sitemap The sitemap to serve.
	 *
	 * @return void
	 */
	private function serve_sitemap( $sitemap ) {
		if ( $sitemap->can_handle_request() ) {
			if ( $sitemap->is_enabled() ) {
				$sitemap->serve();
			} else {
				$sitemap->do_fallback();
			}
		}
	}

	/**
	 * Maybe flush the rewrite rules.
	 *
	 * @return void
	 */
	protected function maybe_flush_rewrite_rules() {
		$flushed = get_option( self::SITEMAP_REWRITE_RULES_FLUSHED, false );
		if ( SMARTCRAWL_VERSION !== $flushed ) {
			flush_rewrite_rules();
			update_option( self::SITEMAP_REWRITE_RULES_FLUSHED, SMARTCRAWL_VERSION );
		}
	}

	/**
	 * Add rewrite rules for the XSL styling.
	 *
	 * @return void
	 */
	private function add_styling_rewrites() {
		global $wp;
		$wp->add_query_var( 'wds_sitemap_styling' );
	}

	/**
	 * Maybe serve the XSL stylesheet.
	 *
	 * @return void
	 */
	private function maybe_serve_xsl_stylesheet() {
		if ( $this->is_styling_request() ) {
			$this->output_xsl();
		}
	}

	/**
	 * Check if the request is for styling.
	 *
	 * @return string The query variable for styling.
	 */
	private function is_styling_request() {
		return (string) get_query_var( 'wds_sitemap_styling' );
	}

	/**
	 * Output the XSL stylesheet.
	 *
	 * @return void
	 */
	private function output_xsl() {
		if ( ! headers_sent() ) {
			$whitelabel = \smartcrawl_get_array_value( $_GET, 'whitelabel' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$template   = \smartcrawl_get_array_value( $_GET, 'template' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$xsl        = \SmartCrawl\Simple_Renderer::load(
				'sitemap/sitemap-xsl',
				array(
					'whitelabel' => $whitelabel,
					'template'   => $template,
				)
			);

			status_header( 200 );
			header( 'Content-Type: text/xsl; charset=UTF-8' );

			die( $xsl ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
}