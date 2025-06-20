<?php
/**
 * Template: Metabox Social Opengraph.
 *
 * @package Smartcrwal
 */

namespace SmartCrawl;

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
$post = empty( $post ) ? null : $post;
if ( ! $post ) {
	return;
}
$smartcrawl_post = \SmartCrawl\Cache\Post_Cache::get()->get_post( $post->ID );
if ( ! $smartcrawl_post ) {
	return;
}

$og = \smartcrawl_get_value( 'opengraph', $smartcrawl_post->get_post_id() );
if ( ! is_array( $og ) ) {
	$og = array();
}
$og = wp_parse_args(
	$og,
	array(
		'title'       => false,
		'description' => false,
		'images'      => false,
		'disabled'    => false,
	)
);
if ( ! is_array( $og['images'] ) ) {
	$og['images'] = array();
}
$images = array_filter(
	$og['images'],
	function ( $image ) {
		return wp_get_attachment_image_src( $image );
	}
);

$this->render_view(
	'metabox/metabox-social-meta-tags',
	array(
		'main_title'              => __( 'OpenGraph', 'wds' ),
		'main_description'        => __( 'OpenGraph is used on many social networks such as Facebook.', 'wds' ),
		'field_name'              => 'wds-opengraph',
		'disabled'                => (bool) \smartcrawl_get_array_value( $og, 'disabled' ),
		'current_title'           => $og['title'],
		'title_placeholder'       => $smartcrawl_post->get_opengraph_title(),
		'current_description'     => $og['description'],
		'description_placeholder' => $smartcrawl_post->get_opengraph_description(),
		'images'                  => $images,
		'single_image'            => false,
	)
);