<?php
/**
 * Template: SEO Health Settings.
 *
 * @package SmartCrawl
 */

$active_tab = empty( $active_tab ) ? '' : $active_tab;
?>
<form id="wds-settings-form">
	<?php
	$this->render_view(
		'vertical-tab',
		array(
			'tab_id'       => 'tab_settings',
			'tab_name'     => esc_html__( 'Settings', 'wds' ),
			'is_active'    => 'tab_settings' === $active_tab,
			'button_text'  => false,
			'tab_sections' => array(
				array(
					'section_template' => 'health/health-test-mode-lighthouse',
				),
			),
		)
	);
	?>
</form>