<?php
/**
 * Template: Dashboard Reports Widget.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl;

use SmartCrawl\Services\Service;

$health_available  = is_main_site();
$crawler_available = \SmartCrawl\Sitemaps\Utils::crawler_available();
if ( ! $health_available && ! $crawler_available ) {
	return;
}
$service  = Service::get( Service::SERVICE_SITE );
$template = $service->is_member() ? 'dashboard/dashboard-reports-full' : 'dashboard/dashboard-reports-free';
$this->render_view( $template );