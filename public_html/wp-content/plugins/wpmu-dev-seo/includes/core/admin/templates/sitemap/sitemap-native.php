<?php
/**
 * Template: Sitemap Native.
 *
 * @package Smartcrwal
 */

namespace SmartCrawl;

use SmartCrawl\Sitemaps\Utils;

$post_types            = empty( $post_types ) ? array() : $post_types;
$taxonomies            = empty( $taxonomies ) ? array() : $taxonomies;
$smartcrawl_buddypress = empty( $smartcrawl_buddypress ) ? array() : $smartcrawl_buddypress;
$extra_urls            = empty( $extra_urls ) ? '' : $extra_urls;
$ignore_urls           = empty( $ignore_urls ) ? '' : $ignore_urls;
$ignore_post_ids       = empty( $ignore_post_ids ) ? '' : $ignore_post_ids;
$option_name           = empty( $_view['option_name'] ) ? '' : $_view['option_name'];
$items_per_sitemap     = Utils::get_items_per_sitemap();
$max_items_per_sitemap = Utils::get_max_items_per_sitemap();

$this->render_view(
	'notice',
	array(
		'message' => sprintf(
			/* translators: 1: Link to WordPress native sitemap.xml, 2,3: strong tag, 4: plugin title */
			esc_html__( 'Your sitemap is available at %1$s. Note that you\'re using the default WordPress sitemap but can switch to %2$s%4$s%3$s\'s advanced sitemaps at any time.', 'wds' ),
			\smartcrawl_format_link( '%s', home_url( '/wp-sitemap.xml' ), '/wp-sitemap.xml', '_blank' ),
			'<strong>',
			'</strong>',
			\smartcrawl_get_plugin_title()
		),
		'class'   => 'sui-notice-info',
	)
);
?>
	<div class="sui-box-settings-row">
		<div class="sui-box-settings-col-1">
			<label class="sui-settings-label">
				<?php
				printf(
					/* translators: 1: plugin title */
					esc_html__( 'Switch to %1$s Sitemap', 'wds' ),
					esc_html( \smartcrawl_get_plugin_title() )
				);
				?>
			</label>
			<p class="sui-description">
				<?php
				printf(
				/* translators: 1,2: strong tag, 3: plugin title */
					esc_html__( 'Switch to the powerful and styled %1$s%3$s%2$s sitemap to ensure that search engines index all your posts and pages.', 'wds' ),
					'<strong>',
					'</strong>',
					esc_html( \smartcrawl_get_plugin_title() )
				);
				?>
			</p>
		</div>
		<div class="sui-box-settings-col-2">
			<button
				type="button"
				id="wds-switch-to-smartcrawl-sitemap"
				class="sui-button sui-button-ghost"
			>
				<span class="sui-icon-defer" aria-hidden="true"></span>
				<?php esc_html_e( 'Switch', 'wds' ); ?>
			</button>

			<p class="sui-description">
				<?php esc_html_e( 'Note: WordPress core sitemaps will be disabled.', 'wds' ); ?>
			</p>
		</div>
	</div>

	<div class="sui-box-settings-row">
		<div class="sui-box-settings-col-1">
			<label class="sui-settings-label">
				<?php esc_html_e( 'Number of links', 'wds' ); ?>
			</label>
			<p class="sui-description">
				<?php esc_html_e( 'Change the number of links in a single sitemap.', 'wds' ); ?>
			</p>
		</div>
		<div class="sui-box-settings-col-2">
			<div class="sui-form-field sui-col">
				<label for="native-items-per-sitemap" class="sui-label">
					<?php esc_html_e( 'Number of links per sitemap', 'wds' ); ?>
				</label>
				<input
					type="number"
					id="native-items-per-sitemap"
					class="sui-form-control sui-input-sm"
					value="<?php echo esc_attr( $items_per_sitemap ); ?>"
					name="<?php echo esc_attr( $option_name ); ?>[items-per-sitemap]"
				>
				<p class="sui-description">
					<?php
					printf(
						/* translators: %s: Maximum number of links in each sitemap */
						esc_html__( 'Choose how many links each sitemap has, up to %d.', 'wds' ),
						esc_html( $max_items_per_sitemap )
					);
					?>
				</p>
			</div>
		</div>
	</div>
<?php
$this->render_view( 'sitemap/sitemap-switch-to-smartcrawl-modal', array() );

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

$this->render_view(
	'sitemap/sitemap-deactivate-button',
	array(
		'label_description'  => esc_html__( 'If you no longer wish to customize the Wordpress core sitemaps  you can deactivate it.', 'wds' ),
		'button_description' => sprintf(
			/* translators: 1,2: strong tag, 3: plugin title */
			esc_html__( 'Note: By clicking this button you are disabling %1$s%3$s%2$s\'s sitemap module. The Wordpress core sitemap will still be available afterwards.', 'wds' ),
			'<strong>',
			'</strong>',
			\smartcrawl_get_plugin_title()
		),
	)
);