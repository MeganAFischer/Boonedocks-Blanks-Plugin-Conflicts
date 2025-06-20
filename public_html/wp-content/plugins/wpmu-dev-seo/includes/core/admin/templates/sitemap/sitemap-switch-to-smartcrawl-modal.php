<?php
/**
 * Template: Sitemap Switch To SmartCrawl Modal.
 *
 * @package Smartcrwal
 */

$this->render_view(
	'modal',
	array(
		'id'            => 'wds-switch-to-smartcrawl-modal',
		'title'         => esc_html__( 'Are you sure?', 'wds' ),
		'description'   => sprintf(
			/* translators: 1,2: strong tag, 3: plugin title */
			esc_html__( 'Please confirm that you wish to switch to %1$s%3$s%2$s\'s powerful sitemap. You can switch back to the WordPress core sitemap at anytime.', 'wds' ),
			'<strong>',
			'</strong>',
			esc_html( \smartcrawl_get_plugin_title() )
		),
		'body_template' => 'sitemap/sitemap-switch-to-smartcrawl-modal-body',
		'small'         => true,
	)
);