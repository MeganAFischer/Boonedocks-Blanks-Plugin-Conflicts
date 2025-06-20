<?php
/**
 * Handles permalinks to report.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Controllers;

use SmartCrawl\Settings;
use SmartCrawl\Singleton;
use SmartCrawl\Admin\Settings\Admin_Settings;

/**
 * Report_Permalinks Controller.
 */
class Report_Permalinks extends Controller {

	use Singleton;

	const ACTION_QV           = 'load-report';
	const ACTION_AUDIT_REPORT = 'seo-audit';
	const ACTION_CRAWL_REPORT = 'sitemap-crawler';

	/**
	 * Initializes action hooks.
	 */
	protected function init() {
		add_action( 'wp', array( $this, 'intercept' ) );
	}

	/**
	 * Intercepts the front page request and redirects based on query parameter.
	 *
	 * @return void
	 */
	public function intercept() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! is_front_page() || ! isset( $_GET[ self::ACTION_QV ] ) ) {
			return;
		}

		$url = false;

		if ( self::ACTION_AUDIT_REPORT === $_GET[ self::ACTION_QV ] ) {
			$url = Admin_Settings::admin_url( Settings::TAB_HEALTH );
		} elseif ( self::ACTION_CRAWL_REPORT === $_GET[ self::ACTION_QV ] ) {
			$url = Admin_Settings::admin_url( Settings::TAB_SITEMAP );
		}

		if ( $url ) {
			wp_safe_redirect( apply_filters( 'smartcrawl_report_admin_url', $url ) );
			exit;
		}
		// phpcs:enable
	}
}