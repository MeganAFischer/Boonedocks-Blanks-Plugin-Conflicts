<?php
/**
 * Template: Onpage BuddyPress Profile section.
 *
 * @package Smartcrwal
 */

use SmartCrawl\Admin\Settings\Onpage;

$meta_robots_bp_profile = empty( $meta_robots_bp_profile ) ? array() : $meta_robots_bp_profile;
$this->render_view( 'onpage/onpage-preview' );
$macros = array_merge(
	Onpage::get_bp_profile_macros(),
	Onpage::get_general_macros()
);

$this->render_view(
	'onpage/onpage-general-settings',
	array(
		'title_key'       => 'title-bp_profile',
		'description_key' => 'metadesc-bp_profile',
		'macros'          => $macros,
	)
);

$this->render_view(
	'onpage/onpage-og-twitter',
	array(
		'for_type'            => 'bp_profile',
		'social_label_desc'   => esc_html__( 'Enable or disable support for social platforms when a BuddyPress profile is shared on them.', 'wds' ),
		'og_description'      => esc_html__( 'OpenGraph support enhances how your content appears when shared on social networks such as Facebook.', 'wds' ),
		'twitter_description' => esc_html__( 'Twitter Cards support enhances how your content appears when shared on Twitter.', 'wds' ),
		'macros'              => $macros,
	)
);

$this->render_view(
	'onpage/onpage-meta-robots',
	array(
		'items' => $meta_robots_bp_profile,
	)
);