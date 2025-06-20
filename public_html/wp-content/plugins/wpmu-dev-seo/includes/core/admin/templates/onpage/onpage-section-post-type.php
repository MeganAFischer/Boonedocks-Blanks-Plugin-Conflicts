<?php
/**
 * Template: Onpage Post Type section.
 *
 * @package Smartcrwal
 */

namespace SmartCrawl;

use SmartCrawl\Admin\Settings\Onpage;

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
$post_type        = empty( $post_type ) ? '' : $post_type;
$post_type_object = empty( $post_type_object ) ? '' : $post_type_object;
$post_type_robots = empty( $post_type_robots ) ? array() : $post_type_robots;
$singular_name    = empty( $post_type_object->labels->singular_name ) ? 'post' : strtolower( $post_type_object->labels->singular_name );
/* translators: %s: Singular post type name */
$og_description = esc_html__( 'OpenGraph support enhances how your content appears when shared on social networks such as Facebook. You can set default values here but also customize this per %s via the post editor.', 'wds' );
$og_description = sprintf( $og_description, $singular_name );
/* translators: %s: Singular post type name */
$twitter_description = esc_html__( 'Twitter Cards support enhances how your content appears when shared on Twitter. You can set default values here but also customize this per %s via the post editor.', 'wds' );
$twitter_description = sprintf( $twitter_description, $singular_name );
$macros              = array_merge(
	Onpage::get_singular_macros( $post_type ),
	Onpage::get_general_macros()
);

$this->render_view( 'onpage/onpage-preview' );

$this->render_view(
	'onpage/onpage-general-settings',
	array(
		'title_key'       => 'title-' . $post_type,
		'description_key' => 'metadesc-' . $post_type,
		'macros'          => $macros,
	)
);

$this->render_view(
	'onpage/onpage-og-twitter',
	array(
		'for_type'            => $post_type,
		'social_label_desc'   => esc_html__( 'Enable or disable support for social platforms when this post type is shared on them.', 'wds' ),
		'og_description'      => $og_description,
		'twitter_description' => $twitter_description,
		'macros'              => $macros,
	)
);

$this->render_view(
	'onpage/onpage-meta-robots',
	array(
		'items' => $post_type_robots,
	)
);