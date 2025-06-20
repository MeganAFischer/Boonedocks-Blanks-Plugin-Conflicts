<?php
/**
 * Template: Onboard modal footer.
 *
 * @package SmartCrawl
 */

?>

<div class="wds-onboard-footer-skip">
	<a
		class="onboard-skip sui-button sui-button-ghost"
		href="#"
		aria-label="<?php esc_html_e( 'Close this dialog window and skip onboarding', 'wds' ); ?>">
		<?php esc_html_e( 'Skip', 'wds' ); ?>
	</a>
</div>

<div class="wds-onboard-footer-start">
	<button type="button" class="sui-button sui-button-blue wds-onboarding-setup">
		<?php esc_html_e( 'Get started', 'wds' ); ?>
	</button>
</div>