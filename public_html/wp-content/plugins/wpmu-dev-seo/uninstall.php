<?php
/**
 * Uninstall file
 *
 * @package SmartCrawl
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// Bail if another version is active.
if ( defined( 'SMARTCRAWL_VERSION' ) ) {
	return;
}

define( 'SMARTCRAWL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once dirname( __FILE__ ) . '/constants.php';
require_once dirname( __FILE__ ) . '/vendor/autoload.php';
require_once dirname( __FILE__ ) . '/deprecated-aliases.php';

// Init plugin.
new \SmartCrawl\Init();
// Uninstall.
\SmartCrawl\Controllers\Data::get()->uninstall();