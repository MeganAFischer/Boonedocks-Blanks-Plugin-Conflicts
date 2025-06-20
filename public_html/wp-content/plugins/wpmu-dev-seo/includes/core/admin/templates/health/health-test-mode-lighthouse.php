<?php
/**
 * Template: SEO Health Lighthouse mode test.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl;

use SmartCrawl\Lighthouse\Options;

$option_name      = empty( $_view['option_name'] ) ? '' : $_view['option_name'];
$dashboard_device = Options::dashboard_widget_device();
?>
<div class="sui-box-settings-row">
	<div class="sui-box-settings-col-1">
		<label class="sui-settings-label">
			<?php esc_html_e( 'Dashboard Widget', 'wds' ); ?>
		</label>
		<p class="sui-description">
			<?php esc_html_e( 'Choose which device you want to show the SEO test results for on the Dashboard widget.', 'wds' ); ?>
		</p>
	</div>

	<div class="sui-box-settings-col-2">
		<?php
		$this->render_view(
			'side-tabs',
			array(
				'id'    => 'wds-lighthouse-dashboard-widget-device',
				'name'  => "{$option_name}[lighthouse-dashboard-widget-device]",
				'value' => $dashboard_device,
				'tabs'  => array(
					array(
						'label' => esc_html__( 'Desktop', 'wds' ),
						'value' => 'desktop',
					),
					array(
						'label' => esc_html__( 'Mobile', 'wds' ),
						'value' => 'mobile',
					),
				),
			)
		);
		?>
	</div>
</div>

<div class="sui-box-footer">
	<button class="sui-button sui-button-blue">
		<span class="sui-loading-text">
			<span class="sui-icon-save" aria-hidden="true"></span>
			<?php esc_html_e( 'Save Settings', 'wds' ); ?>
		</span>
		<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
	</button>
</div>