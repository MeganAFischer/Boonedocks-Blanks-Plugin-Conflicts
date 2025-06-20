<?php
/**
 * Template: Dashboard Advanced Tools Widget.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl;

use SmartCrawl\Admin\Settings\Admin_Settings;
use SmartCrawl\Admin\Settings\Dashboard;
use SmartCrawl\Modules\Advanced\Robots\Controller;
use SmartCrawl\Services\Service;

if ( ! Admin_Settings::is_tab_allowed( Settings::ADVANCED_MODULE ) ) {
	return;
}

$is_active = \SmartCrawl\Modules\Advanced\Controller::get()->should_run();

$settings_opts = Settings::get_specific_options( Settings::SETTINGS_MODULE . '_options' );

if ( ! $is_active && \smartcrawl_get_array_value( $settings_opts, 'hide_disables', true ) ) {
	return '';
}

$page_url = Admin_Settings::admin_url( Settings::ADVANCED_MODULE );

$service   = Service::get( Service::SERVICE_SITE );
$is_member = $service->is_member();
?>

<section
	id="<?php echo esc_attr( Dashboard::BOX_ADVANCED_TOOLS ); ?>"
	class="sui-box wds-dashboard-widget"
>
	<div class="sui-box-header">
		<h2 class="sui-box-title">
			<span class="sui-icon-wand-magic" aria-hidden="true"></span> <?php esc_html_e( 'Advanced Tools', 'wds' ); ?>
		</h2>
	</div>

	<div class="sui-box-body">
		<p><?php esc_html_e( 'Advanced tools focus on the finer details of SEO including internal linking, redirections and Moz analysis.', 'wds' ); ?></p>

		<?php if ( $is_active ) : ?>

			<?php do_action( 'smartcrawl_widget_advanced_submodules' ); ?>

		<?php endif; ?>

	</div>

	<div class="sui-box-footer">
		<?php if ( $is_active ) : ?>
			<a
				href="<?php echo esc_attr( $page_url ); ?>"
				aria-label="<?php esc_html_e( 'Configure advanced tools', 'wds' ); ?>"
				class="sui-button sui-button-ghost"
			>
				<span
					class="sui-icon-wrench-tool"
					aria-hidden="true"></span> <?php esc_html_e( 'Configure', 'wds' ); ?>
			</a>
		<?php else : ?>
			<button
				type="button"
				data-module="advanced"
				data-value="0"
				class="wds-activate-module wds-disabled-during-request sui-button sui-button-blue">
				<span class="sui-loading-text"><?php esc_html_e( 'Activate', 'wds' ); ?></span>
				<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
			</button>
		<?php endif; ?>
	</div>
	<?php if ( ! $is_member ) : ?>
		<div class="sui-box-body">
			<?php
			$this->render_view(
				'mascot-message',
				array(
					'key'         => 'seo-checkup-upsell',
					'dismissible' => false,
					'message'     => sprintf(
						'%s <a target="_blank" class="sui-button sui-button-purple" href="https://wpmudev.com/project/smartcrawl-wordpress-seo/?utm_source=smartcrawl&utm_medium=plugin&utm_campaign=smartcrawl_dash_reports_upsell_notice">%s</a>',
						esc_html__( 'Upgrade to Pro and automatically link your articles both internally and externally with automatic linking - a favourite among SEO pros.', 'wds' ),
						esc_html__( 'Unlock now with Pro', 'wds' )
					),
				)
			);
			?>
		</div>
	<?php endif; ?>
</section>