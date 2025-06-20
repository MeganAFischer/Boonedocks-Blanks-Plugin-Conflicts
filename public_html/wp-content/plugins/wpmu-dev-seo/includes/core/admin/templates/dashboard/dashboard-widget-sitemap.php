<?php
/**
 * Template: Dashboard Sitemap Widget.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl;

use SmartCrawl\Admin\Settings\Dashboard;
use SmartCrawl\Services\Service;
use SmartCrawl\Sitemaps\Utils;
use SmartCrawl\Admin\Settings\Admin_Settings;

$sitemap_available         = Admin_Settings::is_tab_allowed( Settings::TAB_SITEMAP );
$sitemap_crawler_available = Utils::crawler_available();
if ( ! $sitemap_available ) {
	return;
}

$page_url        = Admin_Settings::admin_url( Settings::TAB_SITEMAP );
$options         = $_view['options'];
$sitemap_enabled = Settings::get_setting( 'sitemap' );
$option_name     = Settings::SETTINGS_MODULE . '_options';
$service         = Service::get( Service::SERVICE_SITE );
$is_member       = $service->is_member();
$override_native = Utils::override_native();
$tooltip_text    = $override_native
	? esc_html__( 'You can switch to the WordPress core sitemap through the configure button.', 'wds' )
	: sprintf(
		/* translators: %s: plugin title */
		esc_html__( "You're using the default WordPress sitemap. You can switch to %s's advanced sitemaps at any time.", 'wds' ),
		esc_html( \smartcrawl_get_plugin_title() )
	);
$sitemap_notice_text = \smartcrawl_format_link(
	/* translators: %s: Link to sitemap.xml */
	esc_html__( 'Your sitemap is available at %s', 'wds' ),
	\smartcrawl_get_sitemap_url(),
	'/sitemap.xml',
	'_blank'
);
$core_sitemap_notice_text = \smartcrawl_format_link(
	/* translators: %s: Link to WP-Core sitemap url */
	esc_html__( 'Your WordPress core sitemap is available at %s', 'wds' ),
	home_url( '/wp-sitemap.xml' ),
	'/wp-sitemap.xml',
	'_blank'
);
$news_sitemap_notice_text = \smartcrawl_format_link(
	/* translators: %s: Link to news sitemap url */
	esc_html__( 'Your news sitemap is available at %s', 'wds' ),
	\smartcrawl_get_news_sitemap_url(),
	'/news-sitemap.xml',
	'_blank'
);
$news_sitemap_enabled = \smartcrawl_get_array_value( $options, 'enable-news-sitemap' );

$settings_opts = Settings::get_specific_options( $option_name );
$hide_disables = \smartcrawl_get_array_value( $settings_opts, 'hide_disables', true );

if ( ! $sitemap_enabled && $hide_disables ) {
	return '';
}
?>
<section
	id="<?php echo esc_attr( Dashboard::BOX_SITEMAP ); ?>"
	class="sui-box wds-dashboard-widget"
	data-dependent="<?php echo esc_attr( Dashboard::BOX_TOP_STATS ); ?>">

	<div class="sui-box-header">
		<h2 class="sui-box-title">
			<span class="sui-icon-web-globe-world" aria-hidden="true"></span> <?php esc_html_e( 'Sitemaps', 'wds' ); ?>
		</h2>
		<?php
		if ( $sitemap_enabled && $is_member && $sitemap_crawler_available ) {
			$this->render_view(
				'url-crawl-master',
				array(
					'progress_template' => 'dashboard/dashboard-box-title-url-crawl-in-progress',
					'ready_template'    => 'dashboard/dashboard-box-title-url-crawl-stats',
				)
			);
		}
		?>
	</div>
	<div class="sui-box-body">
		<p><?php esc_html_e( 'Automatically generate detailed sitemaps to tell search engines what content you want them to crawl and index.', 'wds' ); ?></p>

		<div class="wds-separator-top wds-draw-left-padded">
			<small><strong><?php esc_html_e( 'XML Sitemap', 'wds' ); ?></strong></small>
			<?php if ( $sitemap_enabled ) : ?>
				<span
					class="wds-sitemap-type-tag sui-tag sui-tooltip sui-tooltip-constrained"
					data-tooltip="<?php echo esc_attr( $tooltip_text ); ?>">
					<?php
					echo $override_native
						/* translators: 1: plugin title */
						? sprintf( esc_html__( '%s Sitemap', 'wds' ), esc_html( \smartcrawl_get_plugin_title() ) )
						: esc_html__( 'WP Core Sitemap', 'wds' );
					?>
				</span>

				<?php
				$this->render_view(
					'notice',
					array(
						'class'   => 'sui-notice-info',
						'message' => $override_native ? $sitemap_notice_text : $core_sitemap_notice_text,
					)
				);
				?>

			<?php else : ?>
				<p>
					<small><?php esc_html_e( 'Enables an XML page that search engines will use to crawl and index your website pages.', 'wds' ); ?></small>
				</p>

				<?php
				$this->render_view(
					'dismissable-notice',
					array(
						'key'     => 'dashboard-sitemap-disabled-warning',
						'message' => __( 'Your sitemap is currently disabled. We highly recommend you enable this feature if you don’t already have a sitemap.', 'wds' ),
						'class'   => 'sui-notice-warning',
					)
				);
				?>
				<button
					type="button"
					data-option-id="<?php echo esc_attr( $option_name ); ?>"
					data-flag="<?php echo 'sitemap'; ?>"
					aria-label="<?php esc_html_e( 'Activate sitemap component', 'wds' ); ?>"
					class="wds-activate-component sui-button sui-button-blue wds-disabled-during-request">

					<span class="sui-loading-text"><?php esc_html_e( 'Activate', 'wds' ); ?></span>
					<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
				</button>
			<?php endif; ?>
		</div>

		<?php if ( $news_sitemap_enabled ) : ?>
			<div class="wds-separator-top wds-draw-left-padded">
				<small><strong><?php esc_html_e( 'News Sitemap', 'wds' ); ?></strong></small>

				<?php
				$this->render_view(
					'notice',
					array(
						'class'   => 'sui-notice-info',
						'message' => $news_sitemap_notice_text,
					)
				);
				?>
			</div>
		<?php endif; ?>

		<?php if ( $sitemap_crawler_available ) : ?>
			<div class="wds-separator-top cf <?php echo $is_member ? 'wds-draw-left-padded' : 'wds-box-blocked-area wds-draw-down wds-draw-left'; ?>">
				<small><strong><?php esc_html_e( 'URL Crawler', 'wds' ); ?></strong></small>
				<?php if ( $is_member ) : ?>
					<?php if ( $sitemap_enabled ) : ?>
						<?php
						$this->render_view(
							'url-crawl-master',
							array(
								'ready_template'    => 'dashboard/dashboard-url-crawl-stats',
								'progress_template' => 'dashboard/dashboard-url-crawl-in-progress',
								'no_data_template'  => 'dashboard/dashboard-url-crawl-no-data-small',
							)
						);
						?>
					<?php else : ?>
						<p>
							<small>
								<?php
								printf(
									/* translators: 1,2: strong tag, 3: plugin title */
									esc_html__( 'Automatically schedule %1$s%3$s%2$s to run check for URLs that are missing from your Sitemap.', 'wds' ),
									'<strong>',
									'</strong>',
									esc_html( \smartcrawl_get_plugin_title() )
								);
								?>
							</small>
						</p>
						<div><span class="sui-tag sui-tag-inactive">
							<?php esc_html_e( 'Sitemaps must be activated', 'wds' ); ?>
						</span></div>
					<?php endif; ?>
				<?php else : ?>
					<a
						href="https://wpmudev.com/project/smartcrawl-wordpress-seo/?utm_source=smartcrawl&utm_medium=plugin&utm_campaign=smartcrawl_dash_crawl_pro_tag"
						target="_blank">
						<span
							class="sui-tag sui-tag-pro sui-tooltip"
							data-tooltip="<?php esc_attr_e( 'Upgrade to SmartCrawl Pro', 'wds' ); ?>">
							<?php esc_html_e( 'Pro', 'wds' ); ?>
						</span>
					</a>
					<p>
						<small>
							<?php
							printf(
								/* translators: 1,2: strong tag, 3: plugin title */
								esc_html__( 'Automatically schedule %1$s%3$s%2$s to run check for URLs that are missing from your Sitemap.', 'wds' ),
								'<strong>',
								'</strong>',
								esc_html( \smartcrawl_get_plugin_title() )
							);
							?>
						</small>
					</p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="sui-box-footer">
		<a
			href="<?php echo esc_attr( $page_url ); ?>"
			aria-label="<?php esc_html_e( 'Configure sitemap component', 'wds' ); ?>"
			class="sui-button sui-button-ghost">
			<span
				class="sui-icon-wrench-tool"
				aria-hidden="true"></span> <?php esc_html_e( 'Configure', 'wds' ); ?>
		</a>
	</div>
</section>