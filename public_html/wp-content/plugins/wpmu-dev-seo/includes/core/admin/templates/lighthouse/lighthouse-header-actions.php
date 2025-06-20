<?php
/**
 * Template: Lighthouse Header Actions.
 *
 * @package SmartCrawl
 */

$lighthouse_report = empty( $lighthouse_report ) ? false : $lighthouse_report;
if ( ! $lighthouse_report ) {
	return;
}

if ( $lighthouse_report->has_data() && ! $lighthouse_report->has_errors() ) {
	$disabled          = $lighthouse_report->is_cooling_down();
	$remaining_minutes = $lighthouse_report->get_remaining_cooldown_minutes();
	$tooltip           = $lighthouse_report->is_cooling_down()
		? sprintf(
			/* translators: 1: Remaining cool down minutes, 2: plugin title */
			esc_html__( '%2$s is just catching her breath, you can run another test in %1$s minutes.', 'wds' ),
			$remaining_minutes,
			\smartcrawl_get_plugin_title()
		)
		: false;
} else {
	$disabled = false;
	$tooltip  = false;
}
?>

<?php if ( $lighthouse_report->has_data() || $lighthouse_report->has_errors() ) : ?>
	<span
		class="<?php echo $tooltip ? 'sui-tooltip sui-tooltip-constrained' : ''; ?>"
		style="--tooltip-width: 240px;"
		data-tooltip="<?php echo esc_attr( $tooltip ); ?>"
	>
		<button
			type="submit" <?php disabled( $disabled ); ?>
				id="wds-new-lighthouse-test-button"
			class="sui-button sui-button-blue"
		>
			<span class="sui-loading-text"><?php esc_html_e( 'New Test', 'wds' ); ?></span>
			<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
		</button>
	</span>
<?php endif; ?>