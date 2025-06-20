<?php
/**
 * Recommended plugins functionality.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Controllers;

$file_path = SMARTCRAWL_PLUGIN_DIR . 'external/recommended-notices/notice.php';
if ( ! file_exists( $file_path ) ) {
	return;
}

require_once $file_path;

use SmartCrawl\Singleton;

/**
 * Class Recommended_Plugins
 */
class Recommended_Plugins extends Controller {

	use Singleton;

	/**
	 * Initialize the controller.
	 *
	 * @return true
	 */
	protected function init() {
		do_action(
			'wpmudev-recommended-plugins-register-notice',
			SMARTCRAWL_PLUGIN_BASENAME,
			'SmartCrawl', // Plugin Name.
			array(
				'toplevel_page_wds_wizard',
				'smartcrawl_page_wds_health',
				'smartcrawl_page_wds_onpage',
				'smartcrawl_page_wds_social',
				'smartcrawl_page_wds_sitemap',
				'smartcrawl_page_wds_autolinks',
				'smartcrawl_page_wds_settings',
			),
			array( 'after', '.sui-wrap .sui-header' )
		);

		return true;
	}
}