<?php
/**
 * Template: Sitemap SmartCrawl.
 *
 * @package Smartcrwal
 */

namespace SmartCrawl;

$post_types               = empty( $post_types ) ? array() : $post_types;
$taxonomies               = empty( $taxonomies ) ? array() : $taxonomies;
$smartcrawl_buddypress    = empty( $smartcrawl_buddypress ) ? array() : $smartcrawl_buddypress;
$extra_urls               = empty( $extra_urls ) ? '' : $extra_urls;
$ignore_urls              = empty( $ignore_urls ) ? '' : $ignore_urls;
$ignore_post_ids          = empty( $ignore_post_ids ) ? '' : $ignore_post_ids;
$sitemap_cache            = \SmartCrawl\Sitemaps\Cache::get();
$native_sitemap_available = function_exists( '\wp_sitemaps_get_server' );

if ( $sitemap_cache->is_writable() ) {
	$this->render_view(
		'notice',
		array(
			'message' => sprintf(
				/* translators: %s: Link to sitemap.xml */
				esc_html__( 'Your sitemap is available at %s', 'wds' ),
				sprintf( '<a target="_blank" href="%s">/sitemap.xml</a>', esc_attr( \smartcrawl_get_sitemap_url() ) )
			),
			'class'   => 'sui-notice-info',
		)
	);
} else {
	$this->render_view(
		'notice',
		array(
			'message' => sprintf(
				/* translators: %s: Directory where sitemap.xml should be stored */
				esc_html__( 'Unable to write to sitemap file in: %s', 'wds' ),
				sprintf( '<code>%s</code>', esc_html( $sitemap_cache->get_cache_dir() ) )
			),
			'class'   => 'sui-notice-error',
		)
	);
}
if ( $native_sitemap_available ) {
	?>
	<div class="sui-box-settings-row">
		<div class="sui-box-settings-col-1">
			<label class="sui-settings-label">
				<?php esc_html_e( 'Switch to WP core Sitemap', 'wds' ); ?>
			</label>
			<p class="sui-description">
				<?php esc_html_e( 'Switch to customize the native WordPress core Sitemap.', 'wds' ); ?>
			</p>
		</div>
		<div class="sui-box-settings-col-2">
			<button
				type="button"
				id="wds-switch-to-native-sitemap"
				class="sui-button sui-button-ghost"
			>
				<span class="sui-icon-defer" aria-hidden="true"></span>
				<?php esc_html_e( 'Switch', 'wds' ); ?>
			</button>

			<p class="sui-description">
				<?php
				printf(
					/* translators: %s: plugin title */
					esc_html__( 'Note: %s sitemap will be disabled.', 'wds' ),
					esc_html( \smartcrawl_get_plugin_title() )
				);
				?>
			</p>
		</div>
	</div>
	<?php
}
$this->render_view( 'sitemap/sitemap-switch-to-native-modal', array() );

$this->render_view(
	'sitemap/sitemap-common-settings',
	array(
		'post_types'            => $post_types,
		'taxonomies'            => $taxonomies,
		'smartcrawl_buddypress' => $smartcrawl_buddypress,
		'extra_urls'            => $extra_urls,
		'ignore_urls'           => $ignore_urls,
		'ignore_post_ids'       => $ignore_post_ids,
	)
);