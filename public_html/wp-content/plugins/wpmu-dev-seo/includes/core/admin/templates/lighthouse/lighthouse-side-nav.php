<?php
/**
 * Template: Lighthouse Sidebar nav.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl;

use SmartCrawl\Lighthouse\Options;

$active_tab        = empty( $active_tab ) ? '' : $active_tab;
$lighthouse_report = empty( $lighthouse_report ) ? false : $lighthouse_report;
if ( ! $lighthouse_report ) {
	return;
}
$is_member               = ! empty( $_view['is_member'] );
$lighthouse_cron_enabled = Options::is_cron_enabled() && $is_member;
$is_reporting_enabled    = ! empty( $is_reporting_enabled );

$tab_items = array(
	array(
		'id'        => 'tab_lighthouse',
		'name'      => esc_html__( 'SEO audits', 'wds' ),
		'tag_value' => $lighthouse_report->get_failed_audits_count(),
		'tag_class' => 'sui-tag-warning',
	),
);

if ( $is_reporting_enabled ) {
	$tab_items[] = array(
		'id'        => 'tab_reporting',
		'name'      => esc_html__( 'Reporting', 'wds' ),
		'tag_value' => $is_member ? '' : esc_html__( 'Pro', 'wds' ),
		'tag_class' => $is_member ? '' : 'sui-tag-pro',
		'tick'      => $lighthouse_cron_enabled,
	);
}

$tab_items[] = array(
	'id'   => 'tab_settings',
	'name' => esc_html__( 'Settings', 'wds' ),
);

$this->render_view(
	'vertical-tabs-side-nav',
	array(
		'active_tab' => $active_tab,
		'tabs'       => $tab_items,
	)
);