<?php
/**
 * Template: Dashboard Content Analysis Widget.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl;

$seo_analysis_enabled         = Settings::get_setting( 'analysis-seo' );
$readability_analysis_enabled = Settings::get_setting( 'analysis-readability' );
$option_name                  = Settings::TAB_SETTINGS . '_options';
$is_ajax_request              = defined( 'DOING_AJAX' ) && DOING_AJAX;
$refresh_required             = ! $is_ajax_request && ( $seo_analysis_enabled || $readability_analysis_enabled );

$settings_opts = Settings::get_specific_options( Settings::SETTINGS_MODULE . '_options' );
$hide_disables = \smartcrawl_get_array_value( $settings_opts, 'hide_disables', true );

$classes = array();
if ( $refresh_required ) {
	$classes[] = 'wds-box-refresh-required';
}
if ( $seo_analysis_enabled ) {
	$classes[] = 'wds-seo-analysis-enabled';
}
if ( $readability_analysis_enabled ) {
	$classes[] = 'wds-readability-analysis-enabled';
}

if ( ! $seo_analysis_enabled && ! $readability_analysis_enabled && $hide_disables ) {
	return '';
}
?>

<section
	id="<?php echo esc_attr( \SmartCrawl\Admin\Settings\Dashboard::BOX_CONTENT_ANALYSIS ); ?>"
	class="sui-box wds-dashboard-widget <?php echo esc_attr( implode( ' ', $classes ) ); ?>">

	<div class="sui-box-header">
		<h2 class="sui-box-title">
			<span
				class="sui-icon-magnifying-glass-search"
				aria-hidden="true"></span> <?php esc_html_e( 'Content Analysis', 'wds' ); ?>
		</h2>
	</div>

	<div class="sui-box-body">
		<p><?php esc_html_e( 'SEO and Readability Analysis recommend improvements to your content to give it the best chance of ranking highly, as well as being easy for the average person to read.', 'wds' ); ?></p>

		<div class="wds-report">
			<?php if ( $seo_analysis_enabled ) : ?>
				<?php if ( $is_ajax_request ) : ?>
					<?php $this->render_view( 'dashboard/dashboard-content-analysis-seo-overview' ); ?>
				<?php endif; ?>
			<?php elseif ( ! $hide_disables ) : ?>
				<div class="wds-separator-top">
					<small><strong><?php esc_html_e( 'SEO Analysis', 'wds' ); ?></strong></small>
					<p>
						<small><?php esc_html_e( 'Analyses your content against recommend SEO practice and gives recommendations for improvement to make sure content is as optimized as possible.', 'wds' ); ?></small>
					</p>
					<button
						type="button"
						id="wds-activate-analysis-seo"
						aria-label="<?php esc_html_e( 'Activate SEO analysis', 'wds' ); ?>"
						data-option-id="<?php echo esc_attr( $option_name ); ?>"
						data-flag="analysis-seo"
						class="wds-activate-component sui-button sui-button-blue wds-disabled-during-request">

						<span class="sui-loading-text"><?php esc_html_e( 'Activate', 'wds' ); ?></span>
						<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
					</button>
				</div>
			<?php endif; ?>

			<?php if ( $readability_analysis_enabled ) : ?>
				<?php if ( $is_ajax_request ) : ?>
					<?php $this->render_view( 'dashboard/dashboard-content-analysis-readability-overview' ); ?>
				<?php endif; ?>
			<?php elseif ( ! $hide_disables ) : ?>
				<div class="wds-separator-top">
					<small><strong><?php esc_html_e( 'Readability Analysis', 'wds' ); ?></strong></small>
					<p>
						<small><?php esc_html_e( 'Benchmarks the readability of your content for the average visitor and gives recommendations for improvement.', 'wds' ); ?></small>
					</p>
					<button
						type="button"
						id="wds-activate-analysis-readability"
						data-option-id="<?php echo esc_attr( $option_name ); ?>"
						aria-label="<?php esc_html_e( 'Activate readability analysis', 'wds' ); ?>"
						data-flag="analysis-readability"
						class="wds-activate-component sui-button sui-button-blue wds-disabled-during-request">

						<span class="sui-loading-text"><?php esc_html_e( 'Activate', 'wds' ); ?></span>
						<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
					</button>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="sui-box-footer">
		<a
			href="<?php echo esc_attr( admin_url( 'edit.php' ) ); ?>"
			class="sui-button sui-button-ghost">

			<span
				class="sui-icon-pencil"
				aria-hidden="true"></span> <?php esc_html_e( 'Edit Posts', 'wds' ); ?>
		</a>
	</div>
</section>