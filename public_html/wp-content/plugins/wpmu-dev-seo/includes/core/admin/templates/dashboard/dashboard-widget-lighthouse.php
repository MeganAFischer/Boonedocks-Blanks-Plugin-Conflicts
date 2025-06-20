<?php
/**
 * Template: Dashboard Lighhouse Widget.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl;

use SmartCrawl\Admin\Settings\Dashboard;
use SmartCrawl\Lighthouse\Options;
use SmartCrawl\Services\Service;

if ( ! is_main_site() ) {
	return;
}

$lighthouse_start_time = empty( $lighthouse_start_time ) ? false : $lighthouse_start_time;
$lighthouse_error      = $lighthouse_report->has_errors() ? $lighthouse_report->get_error_message() : '';

/**
 * Lighthouse Report.
 *
 * @var \SmartCrawl\Lighthouse\Report|\WP_Error|false $lighthouse_report
 */
$lighthouse_report = empty( $lighthouse_report ) || ! $lighthouse_report->has_data() || $lighthouse_report->has_errors()
	? false
	: $lighthouse_report;
$issue_count       = $lighthouse_report ? $lighthouse_report->get_failed_audits_count() : 0;
$service           = Service::get( Service::SERVICE_SITE );
$is_member         = $service->is_member();
$reporting_enabled = Options::is_cron_enabled() && $is_member;
$tooltip_text      = $lighthouse_report && $lighthouse_report->is_cooling_down()
	? sprintf(
		/* translators: 1: Remaining cool down minutes, 2: plugin title */
		esc_attr__( '%2$s is just catching her breath, you can run another test in %1$s minutes.', 'wds' ),
		$lighthouse_report->get_remaining_cooldown_minutes(),
		\smartcrawl_get_plugin_title()
	)
	: '';
?>
<section
	id="<?php echo esc_attr( Dashboard::BOX_LIGHTHOUSE ); ?>"
	data-dependent="<?php echo esc_attr( Dashboard::BOX_TOP_STATS ); ?>"
	class="sui-box wds-dashboard-widget"
>
	<div class="sui-box-header">
		<h2 class="sui-box-title">
			<span class="wds-lighthouse-icon" aria-hidden="true"></span><?php esc_html_e( 'SEO Audits', 'wds' ); ?>
		</h2>

		<div class="sui-actions-left">
			<span id="wds-device-tag" class="sui-tag sui-tag-disabled sui-tag-sm sui-tag-uppercase">
				<?php
				echo Options::dashboard_widget_device() === 'desktop'
					? esc_html__( 'Desktop', 'wds' )
					: esc_html__( 'Mobile', 'wds' );
				?>
			</span>

			<?php if ( $issue_count > 0 ) : ?>
				<span
					class="sui-tag sui-tag-warning sui-tooltip"
					<?php /* translators: %d: issue count */ ?>
					data-tooltip="<?php echo esc_attr( sprintf( _n( 'You have %d outstanding SEO audit', 'You have %d outstanding SEO audits', $issue_count, 'wds' ), $issue_count ) ); ?>"
				>
					<?php echo intval( $issue_count ); ?>
				</span>
			<?php endif; ?>
		</div>

		<?php if ( ! $lighthouse_start_time && $lighthouse_report ) : ?>
			<div class="sui-actions-right">
				<span
					class="<?php echo $lighthouse_report->is_cooling_down() ? 'sui-tooltip sui-tooltip-constrained' : ''; ?>"
					style="--tooltip-width: 240px;"
					data-tooltip="<?php echo esc_attr( $tooltip_text ); ?>">
					<button class="sui-button sui-button-blue wds-lighthouse-start-test <?php echo $lighthouse_report->is_cooling_down() ? 'disabled' : ''; ?>">
						<span class="sui-loading-text">
							<span class="sui-icon-plus" aria-hidden="true"></span>
							<?php esc_html_e( 'Run Test', 'wds' ); ?>
						</span>
						<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
					</button>
				</span>

			</div>
		<?php elseif ( $lighthouse_start_time && ! $lighthouse_report ) : ?>
			<div class="sui-actions-right">
				<button class="sui-button sui-button-blue wds-lighthouse-start-test disabled wds-run-test-onload">
					<span class="sui-loading-text">
						<span class="sui-icon-plus" aria-hidden="true"></span>
						<?php esc_html_e( 'Run Test', 'wds' ); ?>
					</span>

					<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
				</button>
			</div>
		<?php endif; ?>
	</div>

	<div class="sui-box-body">
		<?php if ( $lighthouse_start_time ) : ?>
			<p><?php esc_html_e( 'Lighthouse is generating a full SEO report of your Homepage, please be patient…', 'wds' ); ?></p>
		<?php elseif ( $lighthouse_report ) : ?>
			<p><?php esc_html_e( 'Ensure that your page is optimized for search engine results ranking. We recommend actioning as many checks as possible.', 'wds' ); ?></p>

			<?php $this->render_view( 'dashboard/dashboard-mini-lighthouse-report' ); ?>
		<?php elseif ( $lighthouse_error ) : ?>
			<?php
			$this->render_view(
				'notice',
				array(
					'message' => $lighthouse_error,
					'class'   => 'sui-notice-error',
				)
			);
			?>
		<?php else : ?>
			<p><?php esc_html_e( 'Lighthouse will run a SEO test against your Homepage, and then it generates a report on how well the page did. From there, use the failing audits as indicators on how to improve your SEO.', 'wds' ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( ! $lighthouse_report && ! $lighthouse_start_time ) : ?>
		<div class="sui-box-footer wds-space-between">
			<button class="sui-button sui-button-blue wds-lighthouse-start-test">
				<span class="sui-loading-text">
					<span class="sui-icon-plus" aria-hidden="true"></span>
					<?php esc_html_e( 'Run Test', 'wds' ); ?>
				</span>

				<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
			</button>

			<span>
				<small>
					<?php
					echo empty( $reporting_enabled )
						? esc_html__( 'Automatic SEO Reports are disabled', 'wds' )
						: esc_html__( 'Automatic SEO Reports are enabled', 'wds' );
					?>
				</small>
			</span>
		</div>
	<?php endif; ?>

</section>