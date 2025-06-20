<?php
/**
 * Template: Dashboard Top bar Sitemap summary.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl;

use SmartCrawl\Services\Service;

$sitemap_enabled = Settings::get_setting( 'sitemap' );
$option_name     = Settings::TAB_SETTINGS . '_options';
$settings_opts   = Settings::get_specific_options( $option_name );
$hide_disables   = \smartcrawl_get_array_value( $settings_opts, 'hide_disables', true );

if ( ! $sitemap_enabled && $hide_disables ) {
	return '';
}

$lighthouse                = Service::get( Service::SERVICE_LIGHTHOUSE );
$is_member                 = $lighthouse->is_member();
$sitemap_crawler_available = \SmartCrawl\Sitemaps\Utils::crawler_available();

if ( ! $sitemap_crawler_available ) {
	return;
}
?>
<li>
	<span class="sui-list-label"><?php esc_html_e( 'Sitemap', 'wds' ); ?></span>
	<span class="sui-list-detail">
		<?php if ( ! $is_member ) : ?>
			<span class="sui-tag sui-tag-inactive"><?php esc_html_e( 'No Data Available', 'wds' ); ?></span>
		<?php elseif ( $sitemap_enabled ) : ?>

			<?php
			$this->render_view(
				'url-crawl-master',
				array(
					'ready_template'    => 'dashboard/dashboard-url-crawl-stats',
					'progress_template' => 'dashboard/dashboard-url-crawl-in-progress-small',
					'no_data_template'  => 'dashboard/dashboard-url-crawl-no-data-small',
				)
			);
			?>

		<?php else : ?>

			<button
				type="button"
				data-option-id="<?php echo esc_attr( $option_name ); ?>"
				data-flag="<?php echo 'sitemap'; ?>"
				aria-label="<?php esc_html_e( 'Activate sitemap component', 'wds' ); ?>"
				class="wds-activate-component wds-disabled-during-request sui-button sui-button-blue"
			>
				<span class="sui-loading-text"><?php esc_html_e( 'Activate Sitemap', 'wds' ); ?></span>
				<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
			</button>

		<?php endif; ?>
	</span>
</li>