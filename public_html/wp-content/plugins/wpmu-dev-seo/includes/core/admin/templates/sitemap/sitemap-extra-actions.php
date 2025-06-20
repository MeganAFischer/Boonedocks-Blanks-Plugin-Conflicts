<?php
/**
 * Template: Sitemap Extra Actions.
 *
 * @package Smartcrwal
 */

namespace SmartCrawl;

use SmartCrawl\Services\Service;

$is_member = ! empty( $_view['is_member'] );

if ( ! $is_member ) {
	return;
}

$service = Service::get( Service::SERVICE_SEO );

/**
 * Report.
 *
 * @var Seo_Report|null $crawl_report
 */
$crawl_report = empty( $_view['crawl_report'] ) ? null : $_view['crawl_report'];

if ( ! $crawl_report ) {
	return;
}

$sitemap_enabled = Settings::get_setting( 'sitemap' );

if ( ! $sitemap_enabled ) {
	return;
}

$crawl_url = \SmartCrawl\Admin\Settings\Sitemap::crawl_url();

$function_name = function_exists( '\wp_date' ) ? 'wp_date' : 'date_i18n';

$end = $service->get_last_run_timestamp();
$end = ! empty( $end ) && is_numeric( $end )
	? call_user_func( $function_name, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $end )
	: __( 'Never', 'wds' );

$cooldown_remaining = $service->get_cooldown_remaining();

$tooltip = $cooldown_remaining ?
	sprintf(
		/* translators: %s: remaining time in hours and minutes */
		esc_html__( 'SEO Crawler is cooling down. Please wait for %s before initiating another scan.', 'wds' ),
		$cooldown_remaining
	) : false;
?>

<span>
	<?php
	printf(
		/* translators: %s: Last crawl date */
		esc_html__( 'Last crawl: %s', 'wds' ),
		esc_html( $end )
	);
	?>
</span>

<span
	class="<?php echo $tooltip ? 'sui-tooltip sui-tooltip-constrained sui-tooltip-left' : ''; ?>"
	style="--tooltip-width: 240px;"
	data-tooltip="<?php echo esc_attr( $tooltip ); ?>"
>
	<a
		href="<?php echo esc_attr( $crawl_url ); ?>"
		class="sui-button sui-button-blue wds-new-crawl-button"
		<?php disabled( (bool) ( $cooldown_remaining || $service->in_progress() ) ); ?>
	>
		<?php esc_html_e( 'New crawl', 'wds' ); ?>
	</a>
</span>