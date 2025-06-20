<?php
/**
 * Template: Onboarding.
 *
 * @package SmartCrawl
 */

$this->render_view(
	'modal',
	array(
		'id'                      => 'wds-onboarding',
		'title'                   => esc_html__( 'Quick setup', 'wds' ),
		'description'             => sprintf(
			/* translators: 1,2: strong tag, 3: plugin title */
			esc_html__( 'Welcome to %1$s%3$s%2$s, the hottest SEO plugin for WordPress! Let\'s quickly set up the basics for you, then you can fine tune each setting as you go - our recommendations are on by default.', 'wds' ),
			'<strong>',
			'</strong>',
			\smartcrawl_get_plugin_title()
		),
		'header_actions_template' => 'dashboard/onboard-modal-header-button',
		'body_template'           => 'dashboard/onboard-modal-body',
		'footer_template'         => 'dashboard/onboard-modal-footer',
	)
);