<?php
/**
 * Frequency content template.
 *
 * @var string $component Component.
 * @var string $frequency Frequency.
 * @var string $dow_value Value.
 * @var string $tod_value Value.
 *
 * @package SmartCrawl
 */

if ( empty( $component ) ) {
	return;
}

if ( 'daily' === $frequency ) : ?>
	<div class="sui-form-field">
		<?php
		$this->render_view(
			'reporting-tod-select',
			array(
				'component' => $component,
				'tod_value' => $tod_value,
			)
		);
		?>
	</div>
<?php elseif ( 'weekly' === $frequency ) : ?>
	<div class="sui-form-field">
		<?php
		$this->render_view(
			'reporting-dow-select',
			array(
				'component' => $component,
				'dow_value' => $dow_value,
			)
		);
		?>
	</div>
<?php elseif ( 'monthly' === $frequency ) : ?>
	<div class="sui-form-field">
		<?php
		$this->render_view(
			'reporting-dom-select',
			array(
				'component' => $component,
				'dom_value' => $dom_value,
			)
		);
		?>
	</div>
	<?php
endif;