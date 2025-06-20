<?php
/**
 * Template: Sitemap No Crawler Data.
 *
 * @package Smartcrwal
 */

namespace SmartCrawl;

$action_url = \SmartCrawl\Admin\Settings\Sitemap::crawl_url();

$this->render_view(
	'disabled-component-inner',
	array(
		'content'         => sprintf(
			/* translators: 1,2: strong tag, 3: plugin title */
			esc_html__( 'Have %1$s%3$s%2$s check for broken URLs, 404s, multiple redirections and other harmful issues that can reduce your ability to rank in search engines.', 'wds' ),
			'<strong>',
			'</strong>',
			esc_html( \smartcrawl_get_plugin_title() )
		),
		'button_text'     => esc_html__( 'Begin Crawl', 'wds' ),
		'button_url'      => $action_url,
		'upgrade_tag'     => 'smartcrawl_sitemap_crawler_upgrade_button',
		'premium_feature' => true,
	)
);