<?php
/**
 * Initializes plugin front-end behavior
 *
 * @package SmartCrawl
 */

namespace SmartCrawl;

use SmartCrawl\Controllers\Controller;

/**
 * Frontend init class.
 *
 * TODO: get rid of this class
 */
class Front extends Controller {

	use Singleton;

	/**
	 * Initializing method.
	 */
	protected function init() {
		if ( defined( '\SMARTCRAWL_EXPERIMENTAL_FEATURES_ON' ) && \SMARTCRAWL_EXPERIMENTAL_FEATURES_ON ) {
			add_action( 'init', array( '\SmartCrawl\Sitemaps\Video\Sitemap', 'serve' ) );

			if ( ! defined( '\SMARTCRAWL_VIDEO_SITEMAP_ALLOW_API_CALLS' ) ) {
				define( 'SMARTCRAWL_VIDEO_SITEMAP_ALLOW_API_CALLS', true );
			}
		}
	}
}