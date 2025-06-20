<?php
/**
 * Template: Dashboard Report on Full version.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl;

use SmartCrawl\Admin\Settings\Dashboard;
use SmartCrawl\Controllers\Cron;
use SmartCrawl\Lighthouse\Options;
use SmartCrawl\Admin\Settings\Admin_Settings;

$options     = empty( $_view['options'] ) ? array() : $_view['options'];
$cron        = Cron::get();
$frequencies = $cron->get_frequencies();

$health_reporting_url = Admin_Settings::admin_url( Settings::TAB_HEALTH ) . '&tab=tab_reporting';
$health_available     = is_main_site();

$lighthouse_cron_enabled  = Options::is_cron_enabled();
$lighthouse_freq          = Options::reporting_frequency();
$lighthouse_freq_readable = \smartcrawl_get_array_value( $frequencies, $lighthouse_freq );

$crawler_available     = \SmartCrawl\Sitemaps\Utils::crawler_available();
$sitemap_enabled       = Settings::get_setting( 'sitemap' );
$crawler_cron_enabled  = ! empty( $_view['options']['crawler-cron-enable'] );
$crawler_reporting_url = Admin_Settings::admin_url( Settings::TAB_SITEMAP ) . '&tab=tab_url_crawler_reporting';
$crawler_freq          = empty( $_view['options']['crawler-frequency'] ) ? false : $_view['options']['crawler-frequency'];
$crawler_freq_readable = \smartcrawl_get_array_value( $frequencies, $crawler_freq );

$site_id = Services\Service::get( Services\Service::SERVICE_SITE )->get_dashboard_site_id();
?>

<section
	id="<?php echo esc_attr( Dashboard::BOX_REPORTS ); ?>"
	data-dependent="<?php echo esc_attr( Dashboard::BOX_REPORTS ); ?>"
	class="sui-box wds-dashboard-widget">

	<div class="sui-box-header">
		<h2 class="sui-box-title">
			<span class="sui-icon-graph-bar" aria-hidden="true"></span><?php esc_html_e( 'Emails & Report', 'wds' ); ?>
		</h2>
	</div>

	<div class="sui-box-body">
		<p><?php esc_html_e( 'Manage your email notifications and report schedules.', 'wds' ); ?></p>

		<table class="sui-table wds-draw-left">
			<tbody>
			<?php if ( $health_available ) : ?>
				<tr>
					<td>
						<span class="wds-lighthouse-icon" aria-hidden="true"></span>
						<small><strong><?php esc_html_e( 'SEO Audits', 'wds' ); ?></strong></small>
					</td>

					<td>
						<?php if ( $lighthouse_cron_enabled ) : ?>
							<span class="sui-tag sui-tag-sm sui-tag-blue"><?php echo esc_html( $lighthouse_freq_readable ); ?></span>
						<?php else : ?>
							<span class="sui-tag sui-tag-sm sui-tag-disabled"><?php esc_html_e( 'Inactive', 'wds' ); ?></span>
						<?php endif; ?>
					</td>

					<td>
						<a
							href="<?php echo esc_attr( $health_reporting_url ); ?>"
							aria-label="<?php esc_html_e( 'Configure SEO audit reports', 'wds' ); ?>">
							<?php if ( $lighthouse_cron_enabled ) : ?>
								<span class="sui-icon-widget-settings-config" aria-hidden="true"></span>
							<?php else : ?>
								<span class="sui-icon-plus" aria-hidden="true"></span>
							<?php endif; ?>
						</a>
					</td>
				</tr>
			<?php endif; ?>

			<?php if ( $crawler_available ) : ?>
				<tr>
					<td>
						<span class="sui-icon-web-globe-world" aria-hidden="true"></span>
						<small><strong><?php esc_html_e( 'Sitemap Crawler', 'wds' ); ?></strong></small>
					</td>

					<td>
						<?php if ( $sitemap_enabled && $crawler_cron_enabled ) : ?>
							<span class="sui-tag sui-tag-sm sui-tag-blue"><?php echo esc_html( $crawler_freq_readable ); ?></span>
						<?php else : ?>
							<span class="sui-tag sui-tag-sm sui-tag-disabled"><?php esc_html_e( 'Inactive', 'wds' ); ?></span>
						<?php endif; ?>
					</td>

					<td>
						<a
							href="<?php echo esc_attr( $crawler_reporting_url ); ?>"
							aria-label="<?php esc_html_e( 'Configure crawler reports', 'wds' ); ?>">
							<?php if ( $crawler_cron_enabled ) : ?>
								<span class="sui-icon-widget-settings-config" aria-hidden="true"></span>
							<?php else : ?>
								<span class="sui-icon-plus" aria-hidden="true"></span>
							<?php endif; ?>
						</a>
					</td>
				</tr>
			<?php endif; ?>
			</tbody>
		</table>

		<p class="sui-description wds-documentation-link">
			<?php
			$hub_url = 'https://wpmudev.com/hub2/';
			if ( $site_id ) {
				$hub_url .= 'site/' . $site_id . '/reports';
			}
			$hub_link = \smartcrawl_format_link(
				/* translators: %s: Link linked to PDF reports in Hub */
				esc_html__( 'You can also set up scheduled PDF reports for your clients via %s.', 'wds' ),
				$hub_url,
				esc_html__( 'The Hub', 'wds' ),
				'_blank'
			);
			echo wp_kses_post( $hub_link );
			?>
		</p>
	</div>
</section>