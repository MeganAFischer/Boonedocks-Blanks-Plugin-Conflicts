<?php
/**
 * Template: Settings Configs section.
 *
 * @package Smartcrwal
 */

namespace SmartCrawl;

$is_active = empty( $is_active ) ? false : $is_active;
wp_enqueue_script( \SmartCrawl\Controllers\Assets::CONFIGS_JS );
?>
<div
	class="wds-vertical-tab-section sui-box tab_configs
	<?php echo $is_active ? '' : 'hidden'; ?>"
	id="tab_configs"
>
	<div id="wds-config-components" class="wds-configs-container">
		<div class="sui-box">
			<div class="sui-box-header">
				<h2 class="sui-box-title">
					<?php esc_html_e( 'Configs', 'wds' ); ?>
				</h2>
			</div>

			<div class="sui-box-body">
				<p>
					<?php
					printf(
						/* translators: 1,2: strong tag, 3: plugin title */
						esc_html__( 'Use configs to save preset configurations of %1$s%3$s%2$s\'s settings, then upload and apply them to your other sites in just a few clicks! You can easily apply configs to multiple sites at once via the Hub.', 'wds' ),
						'<strong>',
						'</strong>',
						esc_html( \smartcrawl_get_plugin_title() )
					);
					?>
				</p>
			</div>
		</div>
	</div>
</div>