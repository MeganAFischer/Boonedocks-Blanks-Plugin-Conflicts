<?php
/**
 * Template: Schema Disabled.
 *
 * @package Smartcrwal
 */

$this->render_view(
	'disabled-component',
	array(
		'content'      => sprintf(
			'%s<br/>',
			esc_html__( 'Quickly add Schema to your pages to help Search Engines understand and show your content better.', 'wds' )
		),
		'component'    => 'schema',
		'button_text'  => esc_html__( 'Activate', 'wds' ),
		'nonce_action' => 'wds-schema-nonce',
	)
);