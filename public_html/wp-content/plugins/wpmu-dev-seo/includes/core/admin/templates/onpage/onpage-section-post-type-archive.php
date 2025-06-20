<?php
/**
 * Template: Onpage Post Type Archive section.
 *
 * @package Smartcrwal
 */

namespace SmartCrawl;

use SmartCrawl\Admin\Settings\Onpage;

$archive_post_type        = empty( $archive_post_type ) ? '' : $archive_post_type;
$archive_post_type_robots = empty( $archive_post_type_robots ) ? '' : $archive_post_type_robots;
$macros                   = array_merge(
	Onpage::get_pt_archive_macros(),
	Onpage::get_general_macros()
);

$this->render_view( 'onpage/onpage-preview' );

$this->render_view(
	'onpage/onpage-general-settings',
	array(
		'title_key'       => 'title-' . $archive_post_type,
		'description_key' => 'metadesc-' . $archive_post_type,
		'macros'          => $macros,
	)
);

$this->render_view(
	'onpage/onpage-og-twitter',
	array(
		'for_type' => $archive_post_type,
		'macros'   => $macros,
	)
);

$this->render_view(
	'onpage/onpage-meta-robots',
	array(
		'items' => $archive_post_type_robots,
	)
);