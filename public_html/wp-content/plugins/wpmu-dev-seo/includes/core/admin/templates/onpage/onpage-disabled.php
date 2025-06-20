<?php
/**
 * Template: Onpage Disabled.
 *
 * @package Smartcrwal
 */

$this->render_view(
	'disabled-component',
	array(
		'content'     => sprintf(
			'%s<br/>',
			esc_html__( 'Change the title and meta settings for your pages.', 'wds' )
		),
		'component'   => 'onpage',
		'button_text' => esc_html__( 'Activate', 'wds' ),
	)
);