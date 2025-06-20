<?php
/**
 * Template: Reporting Schedule.
 *
 * @package Smartcrwal
 */

$frequency            = empty( $frequency ) ? false : $frequency;
$dom_value            = empty( $dom_value ) ? false : $dom_value;
$dow_value            = empty( $dow_value ) ? false : $dow_value;
$tod_value            = empty( $tod_value ) ? false : $tod_value;
$component            = empty( $component ) ? '' : $component;
$excluded_frequencies = empty( $excluded_frequencies ) ? array( 'hourly' ) : $excluded_frequencies;
if ( ! $component ) {
	return;
}

$cron = \SmartCrawl\Controllers\Cron::get();
// This does the actual rescheduling.
$cron->set_up_schedule();
$option_name = empty( $_view['option_name'] ) ? '' : $_view['option_name'];

$frequency_radio_name = "{$option_name}[{$component}-frequency]";
$frequency_radio_id   = "wds-{$component}-frequency-radio";
$pane_id              = "wds-{$component}-frequency-pane";
$frequencies          = $cron->get_frequencies( $excluded_frequencies );
?>

<div
	class="sui-tabs sui-side-tabs"
	id="wds-<?php echo esc_attr( $component ); ?>-frequency-tabs"
>
	<div role="tablist" class="sui-tabs-menu">
		<?php foreach ( $frequencies as $key => $label ) : ?>
			<button
				type="button"
				role="tab"
				id="<?php echo esc_attr( $component ); ?>_<?php echo esc_attr( $key ); ?>__tab"
				class="sui-tab-item <?php echo $key === $frequency ? 'active' : ''; ?>"
				aria-controls="<?php echo esc_attr( $component ); ?>_<?php echo esc_attr( $key ); ?>__content"
				aria-selected="<?php echo $key === $frequency ? 'true' : 'false'; ?>"
			>
				<?php echo esc_html( $label ); ?>
			</button>
			<input
				type="radio"
				name="<?php echo esc_attr( $frequency_radio_name ); ?>"
				value="<?php echo esc_attr( $key ); ?>"
				class="sui-screen-reader-text"
				aria-label="<?php echo esc_html( $label ); ?>"
				aria-hidden="true"
				<?php checked( $key, $frequency ); ?>
			/>
		<?php endforeach; ?>
	</div>

	<div class="sui-tabs-content">
		<?php foreach ( $frequencies as $key => $label ) : ?>
			<div
				role="tabpanel"
				id="<?php echo esc_attr( $component ); ?>_<?php echo esc_attr( $key ); ?>__content"
				class="sui-tab-content <?php echo $key === $frequency ? 'active' : ''; ?>"
				aria-labelledby="<?php echo esc_attr( $component ); ?>_<?php echo esc_attr( $key ); ?>__tab"
				tabindex="0"
				<?php echo $key === $frequency ? '' : 'hidden'; ?>
			>
				<?php if ( 'hourly' !== $key ) : ?>
					<div class="sui-border-frame">
						<?php
						$this->render_view(
							'reporting-frequency-content',
							array(
								'component' => $component,
								'frequency' => $key,
								'dom_value' => $dom_value,
								'dow_value' => $dow_value,
								'tod_value' => $tod_value,
							)
						);
						?>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>