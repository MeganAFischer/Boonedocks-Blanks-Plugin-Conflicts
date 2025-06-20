<?php // phpcs:ignore
/**
 * Missing configs region modal.
 *
 * @package snapshot
 */

use WPMUDEV\Snapshot4\Helper\Settings;

?>

<div class="sui-modal sui-modal-md">
	<div role="dialog" id="snapshot-missing-configs" class="sui-modal-content" aria-modal="true">

		<div id="snapshot-missing-configs-slide-1" class="sui-modal-slide sui-active sui-loaded" data-modal-size="md">
			<div class="sui-box">

				<div class="sui-box-header sui-flatten sui-content-center">

					<div class="sui-box-banner"
						role="banner" aria-hidden="true"></div>

					<h3 class="sui-box-title sui-lg">
						<?php esc_html_e( 'Select Storage Region', 'snapshot' ); ?>
					</h3>
					<span
						class="sui-description"><?php esc_html_e( 'Choose the data center region where you’d like to store your backups for optimal performance and compliance.', 'snapshot' ); ?></span>

				</div>

				<div class="sui-box-body">
					<div class="sui-notice sui-notice-error on-error" style="display: none;">
						<div class="sui-notice-content">
							<div class="sui-notice-message">
								<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
								<?php if ( Settings::get_branding_hide_doc_link() ) { ?>
								<p><?php esc_html_e( 'We were unable to proceed due to a connection problem. Please change the storage region again, or contact support if the problem persists.', 'snapshot' ); ?>
								</p>
								<?php } else { ?>
									<?php /* translators: %s - link */ ?>
								<p><?php echo wp_kses_post( sprintf( __( 'We were unable to proceed due to a connection problem.  Please choose thestorage region again, or <a href="%s" target="_blank">contact our support team</a> if the problem persists.', 'snapshot' ), 'https://wpmudev.com/hub2/support?utm_source=snapshot&utm_medium=email&utm_campaign=snapshot-email-get-support#get-support' ) ); ?>
								</p>
								<?php } ?>
							</div>
						</div>
					</div>

					<form method="post" id="onboarding-region">
						<?php
						wp_nonce_field( 'save_snapshot_region', '_wpnonce-save_snapshot_region' );
						?>
						<div class="sui-form-field missing-region-field">

							<label for="onboarding-select-region-missing" id="label-onboarding-select-region-missing"
								class="sui-label"><?php esc_html_e( 'Storage Region', 'snapshot' ); ?></label>

							<select class="sui-select" id="onboarding-select-region-missing" placeholder="Choose storage region"
								aria-labelledby="label-onboarding-select-region-missing"
								aria-describedby="description-onboarding-select-region-missing">
								<option value="us">
									<?php esc_html_e( 'United States (better performance, recommended)', 'snapshot' ); ?>
								</option>
								<option value="eu">
									<?php esc_html_e( 'Europe (EU data protection directive compliant)', 'snapshot' ); ?>
								</option>
							</select>

						</div>

						<div class="sui-box-footer sui-flatten sui-lg sui-content-center">
							<button type="button" id="snapshot-set-initial-region-missing" class="sui-button"
								onclick="jQuery(window).trigger('snapshot:missing_configs_confirm')">
								<span
									class="sui-button-text-default"><?php esc_html_e( 'Continue', 'snapshot' ); ?></span>
								<span class="sui-button-text-onload">
									<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
									<?php esc_html_e( 'Continue', 'snapshot' ); ?>
								</span>
							</button>
						</div>
					</form>
				</div>

			</div>
		</div>
	</div>
</div>