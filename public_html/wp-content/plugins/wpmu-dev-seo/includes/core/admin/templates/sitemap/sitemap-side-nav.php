<?php
/**
 * Template: Sitemap Sidenav.
 *
 * @package Smartcrwal
 */

namespace SmartCrawl;

$active_tab = empty( $active_tab ) ? '' : $active_tab;

$crawl_report              = empty( $_view['crawl_report'] ) ? null : $_view['crawl_report'];
$active_issues             = empty( $crawl_report ) ? 0 : $crawl_report->get_issues_count();
$is_member                 = empty( $_view['is_member'] ) ? false : true;
$crawler_cron_enabled      = ! empty( $_view['options']['crawler-cron-enable'] ) && $is_member;
$sitemap_crawler_available = empty( $sitemap_crawler_available ) ? false : $sitemap_crawler_available;
$tab_items                 = array();
$override_native           = empty( $override_native ) ? false : $override_native;

$tab_items[] = array(
	'id'   => 'tab_sitemap',
	'name' => $override_native
		? esc_html__( 'General Sitemap', 'wds' )
		: esc_html__( 'WP Core Sitemap', 'wds' ),
);
$tab_items[] = array(
	'id'   => 'tab_news',
	'name' => esc_html__( 'News Sitemap', 'wds' ),
);
if ( $sitemap_crawler_available ) {
	$tab_items[] = array(
		'id'         => 'tab_url_crawler',
		'name'       => esc_html__( 'Crawler', 'wds' ),
		'spinner'    => $crawl_report && $crawl_report->is_in_progress(),
		'tag_value'  => $active_issues > 0 ? $active_issues : false,
		'tag_class'  => 'sui-tag-warning',
		'tick'       => $crawl_report && $crawl_report->has_data() && ! $active_issues,
		'tick_class' => 'sui-success',
	);

	$tab_items[] = array(
		'id'   => 'tab_url_crawler_reporting',
		'name' => esc_html__( 'Reporting', 'wds' ),
		'tick' => $crawler_cron_enabled,
	);
}
if ( $override_native ) {
	$tab_items[] = array(
		'id'   => 'tab_settings',
		'name' => esc_html__( 'Settings', 'wds' ),
	);
}

$this->render_view(
	'vertical-tabs-side-nav',
	array(
		'active_tab' => $active_tab,
		'tabs'       => $tab_items,
	)
);