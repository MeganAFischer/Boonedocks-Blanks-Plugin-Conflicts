<?php
/**
 * Template: Social Settings section.
 *
 * @package Smartcrwal
 */

$option_name    = empty( $_view['option_name'] ) ? '' : $_view['option_name'];
$options        = empty( $options ) ? array() : $options;
$social_options = empty( $social_options ) ? array() : $social_options;

$schema_enable_test_button = (bool) \smartcrawl_get_array_value( $options, 'schema_enable_test_button' );
?>

<div class="sui-box-settings-row">
	<div class="sui-box-settings-col-1">
		<label class="sui-settings-label">
			<?php esc_html_e( 'Deactivate', 'wds' ); ?>
		</label>

		<p class="sui-description">
			<?php esc_html_e( 'Use this option to deactivate the social module from your site.', 'wds' ); ?>
		</p>
	</div>

	<div class="sui-box-settings-col-2">
		<button
			type="button"
			id="wds-deactivate-social-component"
			class="sui-button sui-button-ghost"
		>
			<span class="sui-icon-power-on-off" aria-hidden="true"></span>

			<?php esc_html_e( 'Deactivate', 'wds' ); ?>
		</button>
	</div>
</div>