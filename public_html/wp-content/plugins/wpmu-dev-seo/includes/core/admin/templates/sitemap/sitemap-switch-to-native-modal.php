<?php
/**
 * Template: Sitemap Switch To Native Modal.
 *
 * @package Smartcrwal
 */

$this->render_view(
	'modal',
	array(
		'id'            => 'wds-switch-to-native-modal',
		'title'         => esc_html__( 'Are you sure?', 'wds' ),
		'description'   => sprintf(
			/* translators: 1,2: strong tag, 3: plugin title */
			esc_html__( 'The powerful %1$s%3$s%2$s sitemap ensures search engines index all of your posts and pages. Are you sure you wish to switch to the WordPress core sitemap?', 'wds' ),
			'<strong>',
			'</strong>',
			\smartcrawl_get_plugin_title()
		),
		'body_template' => 'sitemap/sitemap-switch-to-native-modal-body',
		'small'         => true,
	)
);