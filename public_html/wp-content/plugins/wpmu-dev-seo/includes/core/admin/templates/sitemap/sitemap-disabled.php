<?php
/**
 * Template: Sitemap Disabled.
 *
 * @package Smartcrwal
 */

$this->render_view(
	'disabled-component',
	array(
		'content'     => sprintf(
			/* translators: 1,2: strong tag, 3: plugin title, 4: br tag */
			esc_html__( 'Automatically generate a full sitemap, regularly send updates to search engines and set up.%4$s%1$s%3$s%2$s to automatically check URLs are discoverable by search engines.', 'wds' ),
			'<strong>',
			'</strong>',
			\smartcrawl_get_plugin_title(),
			'<br/>'
		),
		'component'   => 'sitemap',
		'button_text' => esc_html__( 'Activate Sitemap', 'wds' ),
	)
);