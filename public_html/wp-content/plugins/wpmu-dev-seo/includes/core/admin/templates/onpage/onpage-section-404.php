<?php
/**
 * Template: Onpage 404 section.
 *
 * @package Smartcrwal
 */

namespace SmartCrawl;

$macros = \SmartCrawl\Admin\Settings\Onpage::get_general_macros();
$this->render_view( 'onpage/onpage-preview' );

$this->render_view(
	'onpage/onpage-general-settings',
	array(
		'title_key'       => 'title-404',
		'description_key' => 'metadesc-404',
		'macros'          => $macros,
	)
);