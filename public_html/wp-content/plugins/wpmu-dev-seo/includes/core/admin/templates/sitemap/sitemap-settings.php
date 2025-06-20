<?php
/**
 * Template: Sitemap Settings.
 *
 * @package Smartcrwal
 */

namespace SmartCrawl;

use SmartCrawl\Admin\Settings\Sitemap;
use SmartCrawl\Services\Service;
use SmartCrawl\Sitemaps\Utils;

$is_member                 = ! empty( $_view['is_member'] );
$active_tab                = empty( $active_tab ) ? '' : $active_tab;
$crawl_report              = empty( $_view['crawl_report'] ) ? null : $_view['crawl_report'];
$smartcrawl_buddypress     = empty( $smartcrawl_buddypress ) ? array() : $smartcrawl_buddypress;
$sitemaps_enabled          = Settings::get_setting( 'sitemap' );
$sitemap_crawler_available = Utils::crawler_available();
$email_recipients          = Sitemap::get_email_recipients();
$override_native           = empty( $override_native ) ? false : $override_native;
$ping_google               = ! empty( $_view['options']['ping-google'] );
$ping_bing                 = ! empty( $_view['options']['ping-bing'] );
?>

<?php $this->render_view( 'before-page-container' ); ?>
<div id="container" class="<?php \smartcrawl_wrap_class( 'wds-sitemap-settings' ); ?>">

	<?php
	$this->render_view(
		'page-header',
		array(
			'title'                 => esc_html__( 'Sitemaps', 'wds' ),
			'documentation_chapter' => 'sitemaps',
			'utm_campaign'          => 'smartcrawl_sitemap_docs',
			'extra_actions'         => $sitemap_crawler_available ? 'sitemap/sitemap-extra-actions' : '',
		)
	);
	?>

	<?php
	$this->render_view(
		'floating-notices',
		array(
			'message' => $ping_google || $ping_bing
				? esc_html__( 'Your Sitemap is updated and Search Engines are being notified with changes.', 'wds' )
				: esc_html__( 'Your sitemap has been updated.', 'wds' ),
			'keys'    => array(
				'wds-email-recipient-notice',
				'wds-sitemap-manually-updated',
				'wds-sitemap-manually-notify-search-engines',
			),
		)
	);
	?>
	<?php $this->render_view( 'sitemap/sitemap-notices' ); ?>

	<?php
	if ( $sitemaps_enabled ) {
		$service            = Service::get( Service::SERVICE_SEO );
		$cooldown_remaining = $service->get_cooldown_remaining();
		?>

		<form action='<?php echo esc_attr( $_view['action_url'] ); ?>' method='post' class="wds-form">
			<?php $this->settings_fields( $_view['option_name'] ); ?>

			<input
				type="hidden"
				name='<?php echo esc_attr( $_view['option_name'] ); ?>[<?php echo esc_attr( $_view['slug'] ); ?>-setup]'
				value="1"
			>
			<?php if ( $sitemap_crawler_available ) : ?>
				<div id="wds-crawl-summary-container">
					<?php
					$this->render_view(
						'sitemap/sitemap-crawl-stats',
						array(
							'crawl_report'    => $crawl_report,
							'override_native' => $override_native,
						)
					);
					?>
				</div>
			<?php endif; ?>

			<?php if ( $cooldown_remaining && ! $service->in_progress() ) : ?>

				<div class="sui-notice sui-notice-grey">
					<div class="sui-notice-content">
						<div class="sui-notice-message">
							<span class="sui-notice-icon sui-md sui-icon-clock" aria-hidden="true"></span>
							<p>
								<?php
								printf(
								/* translators: %s: remaining time in hours and minutes */
									esc_html__( 'SEO Crawler is cooling down. Please wait for %s before initiating another scan.', 'wds' ),
									esc_html( $cooldown_remaining )
								);
								?>
							</p>
						</div>
					</div>
				</div>

			<?php endif; ?>

			<div class="wds-vertical-tabs-container sui-row-with-sidenav" id="sitemap-settings-tabs">

				<?php
				$this->render_view(
					'sitemap/sitemap-side-nav',
					array(
						'active_tab'                => $active_tab,
						'sitemap_crawler_available' => $sitemap_crawler_available,
						'override_native'           => $override_native,
					)
				);

				// The last tab is on top in the markup because we want the item-per-sitemap setting in the native sitemap tab to override the regular field.
				$settings_tab = $this->load_view(
					'vertical-tab',
					array(
						'tab_id'       => 'tab_settings',
						'tab_name'     => esc_html__( 'Settings', 'wds' ),
						'is_active'    => 'tab_settings' === $active_tab,
						'tab_sections' => array(
							array( 'section_template' => 'sitemap/sitemap-section-advanced' ),
						),
					)
				);
				if ( $override_native ) {
					// $settings_tab is escaped in the template file
					echo $settings_tab; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} else {
					/* translators: %s: settings tab */
					printf( '<div style="display: none;">%s</div>', $settings_tab ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>

				<?php
				$this->render_view(
					'sitemap/sitemap-section-news',
					array(
						'is_active' => 'tab_news' === $active_tab,
					)
				);

				$this->render_view(
					'vertical-tab',
					array(
						'tab_id'       => 'tab_sitemap',
						'tab_name'     =>
							$override_native
								? esc_html__( 'General Sitemap', 'wds' )
								: esc_html__( 'WP Core Sitemap', 'wds' ),
						'is_active'    => 'tab_sitemap' === $active_tab,
						'tab_sections' => array(
							array(
								'section_description' => $override_native ? esc_html__( 'Automatically generate a sitemap and regularly send updates to Google.', 'wds' ) : esc_html__( 'Set up your sitemaps to tell search engines what content you want them to crawl and index.', 'wds' ),
								'section_template'    => 'sitemap/sitemap-section-settings',
								'section_args'        => array(
									'post_types'      => $post_types,
									'taxonomies'      => $taxonomies,
									'smartcrawl_buddypress' => $smartcrawl_buddypress,
									'extra_urls'      => ! empty( $extra_urls ) ? $extra_urls : '',
									'ignore_urls'     => ! empty( $ignore_urls ) ? $ignore_urls : '',
									'ignore_post_ids' => ! empty( $ignore_post_ids ) ? $ignore_post_ids : '',
									'override_native' => $override_native,
								),
							),
						),
					)
				);
				?>

				<?php
				if ( $sitemap_crawler_available ) {
					$this->render_view(
						'vertical-tab',
						array(
							'tab_id'              => 'tab_url_crawler',
							'tab_name'            => esc_html__( 'Crawler', 'wds' ),
							'title_actions_left'  => 'sitemap/sitemap-url-crawler-tab-title-left',
							'title_actions_right' => 'sitemap/sitemap-url-crawler-tab-title-right',
							'is_active'           => 'tab_url_crawler' === $active_tab,
							'button_text'         => false,
							'title_button'        => 'upgrade',
							'tab_sections'        => array(
								array(
									'section_template' => 'sitemap/sitemap-section-url-crawler',
								),
							),
						)
					);

					$this->render_view(
						'vertical-tab-upsell',
						array(
							'tab_id'             => 'tab_url_crawler_reporting',
							'tab_name'           => esc_html__( 'Reporting', 'wds' ),
							'is_active'          => 'tab_url_crawler_reporting' === $active_tab,
							'title_actions_left' => 'sitemap/sitemap-reporting-title-pro-tag',
							'button_text'        => $is_member ? esc_html__( 'Save Settings', 'wds' ) : '',
							'tab_sections'       => array(
								array(
									'section_template' => 'sitemap/sitemap-section-reporting',
									'section_args'     => array(
										'email_recipients' => $email_recipients,
									),
								),
							),
						)
					);
				}
				?>
			</div>
		</form>
		<?php
	} else {
		$this->render_view( 'sitemap/sitemap-disabled' );
	}
	?>
	<?php $this->render_view( 'footer' ); ?>
	<?php $this->render_view( 'upsell-modal' ); ?>

</div><!-- end wds-sitemap-settings -->