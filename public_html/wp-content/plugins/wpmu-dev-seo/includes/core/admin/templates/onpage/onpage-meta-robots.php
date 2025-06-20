<?php
/**
 * Template: Onpage Meta Robots.
 *
 * @package Smartcrwal
 */

$items = empty( $items ) ? array() : $items;

if ( ! $items ) {
	return;
}

$this->render_view(
	'toggle-group',
	array(
		'label'       => esc_html__( 'Indexing', 'wds' ),
		'description' => esc_html__( 'Choose whether you want your website to appear in search results.', 'wds' ),
		'separator'   => true,
		'items'       => $items,
	)
);