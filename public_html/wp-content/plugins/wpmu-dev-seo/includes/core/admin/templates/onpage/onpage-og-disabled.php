<?php
/**
 * Template: Onpage OpenGraph Disabled.
 *
 * @package Smartcrwal
 */

namespace SmartCrawl;

use SmartCrawl\Admin\Settings\Admin_Settings;

$message = esc_html__( 'OpenGraph is globally disabled.', 'wds' );
if ( Admin_Settings::is_tab_allowed( Settings::TAB_SOCIAL ) ) {
	$social_page = Admin_Settings::admin_url( Settings::TAB_SOCIAL );
	$message     = sprintf(
		/* translators: 1: Message, 2: Anchor tag to Open Graph page */
		esc_html__( '%1$s You can enable it %2$s.', 'wds' ),
		$message,
		sprintf(
			'<a href="%s">%s</a>',
			esc_url_raw( add_query_arg( 'tab', 'tab_open_graph', $social_page ) ),
			esc_html__( 'here', 'wds' )
		)
	);
}

$this->render_view(
	'notice',
	array(
		'class'   => 'sui-notice-info',
		'message' => $message,
	)
);