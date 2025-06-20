<?php
/**
 * Template: Metabox Social Twitter.
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

$twitter = \smartcrawl_get_value( 'twitter', $post->ID );
if ( ! is_array( $twitter ) ) {
	$twitter = array();
}
$twitter = wp_parse_args(
	$twitter,
	array(
		'title'       => false,
		'description' => false,
		'images'      => false,
		'disabled'    => false,
	)
);

$this->render_view(
	'metabox/metabox-social-meta-tags',
	array(
		'main_title'              => __( 'Twitter', 'wds' ),
		'main_description'        => __( 'These details will be used in Twitter cards.', 'wds' ),
		'field_name'              => 'wds-twitter',
		'disabled'                => (bool) \smartcrawl_get_array_value( $twitter, 'disabled' ),
		'current_title'           => $twitter['title'],
		'title_placeholder'       => $smartcrawl_post->get_twitter_title(),
		'current_description'     => $twitter['description'],
		'description_placeholder' => $smartcrawl_post->get_twitter_description(),
		'images'                  => $twitter['images'],
		'single_image'            => true,
	)
);