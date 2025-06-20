<?php
/**
 * Template: Sitemap Url Crawler section.
 *
 * @package Smartcrwal
 */

namespace SmartCrawl;

$crawl_report = empty( $_view['crawl_report'] ) ? null : $_view['crawl_report'];
$this->render_view(
	'sitemap/sitemap-crawl-content',
	array(
		'crawl_report' => $crawl_report,
	)
);