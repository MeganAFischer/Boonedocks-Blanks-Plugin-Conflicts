<?php
/**
 * Template: Onpage General Settings.
 *
 * @package Smartcrwal
 */

namespace SmartCrawl;

$title_key       = empty( $title_key ) ? '' : $title_key;
$description_key = empty( $description_key ) ? '' : $description_key;

$title_label_desc = empty( $title_label_desc )
	? sprintf(
		/* translators: 1,2: strong tag, 3: plugin title */
		esc_html__( 'Choose the variables from which %1$s%3$s%2$s will automatically generate your SEO title from.', 'wds' ),
		'<strong>',
		'</strong>',
		\smartcrawl_get_plugin_title()
	)
	: $title_label_desc;
$title_field_desc = empty( $title_field_desc )
	? '' : $title_field_desc;
$meta_label_desc  = empty( $meta_label_desc )
	? esc_html__( 'A title needs a description. Choose the variables to automatically generate a description from.', 'wds' ) : $meta_label_desc;
$meta_field_desc  = empty( $meta_field_desc )
	? '' : $meta_field_desc;

$options = empty( $_view['options'] ) ? array() : $_view['options'];

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
$title       = $title_key ? \smartcrawl_get_array_value( $options, $title_key ) : '';
$description = $description_key
	? \smartcrawl_get_array_value( $options, $description_key )
	: '';
$macros      = empty( $macros ) ? array() : $macros;

$this->render_view(
	'onpage/onpage-general-settings-inner',
	array(
		'title_key'        => $title_key,
		'description_key'  => $description_key,
		'title_label_desc' => $title_label_desc,
		'title_field_desc' => $title_field_desc,
		'meta_label_desc'  => $meta_label_desc,
		'meta_field_desc'  => $meta_field_desc,
		'title'            => $title,
		'description'      => $description,
		'macros'           => $macros,
	)
);