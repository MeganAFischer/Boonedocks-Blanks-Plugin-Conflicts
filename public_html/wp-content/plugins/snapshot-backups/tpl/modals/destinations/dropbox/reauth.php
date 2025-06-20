<?php // phpcs:ignore
/**
 * Reauth screen of Dropbox destination modal.
 *
 * @package snapshot
 */
use WPMUDEV\Snapshot4\Task;
use WPMUDEV\Snapshot4\Helper\Settings;

?>
<div class="sui-modal sui-modal-md">
	<div
		role="dialog"
		id="snapshot-reauth-dropbox-destination"
		class="sui-modal-content"
		aria-modal="true"
		aria-labelledby="snapshot-reauth-dropbox-destination-title"
		aria-describedby="snapshot-reauth-dropbox-destination-description"
	>
		<div class="sui-box">

			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">

				<figure class="sui-box-logo" aria-hidden="true">
					<img
						src="<?php echo esc_attr( $assets->get_asset( 'img/destination-logo-dropbox-header.svg' ) ); ?>" />
				</figure>

				<button class="sui-button-icon sui-button-float--right" data-modal-close>
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Close', 'snapshot' ); ?></span>
				</button>

				<h3 id="snapshot-reauth-dropbox-destination-title" class="sui-box-title sui-lg"><?php echo esc_html( __( 'Connect Dropbox', 'snapshot' ) ); ?></h3>
				<span class="sui-description" id="snapshot-reauth-dropbox-destination-description">
					<?php esc_html_e( 'Easily connect with Dropbox to authorize Snapshot and store your backups in their directory.', 'snapshot' ); ?>
				</span>

			</div>

			<div class="sui-box-body">

				<div role="alert" id="snapshot-wrong-dropbox-reauth-creds" class="sui-notice sui-notice-error"
					aria-live="assertive" style="display: none;">
					<div class="sui-notice-content">
						<div class="sui-notice-message">
							<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
							<?php if ( Settings::get_branding_hide_doc_link() ) { ?>
							<p><?php esc_html_e( 'It appears the authorization process went wrong. Please try again by clicking the "Authenticate" button and make sure you authorize Snapshot to access your Dropbox. If you run into further issues, you can contact our Support team for help.', 'snapshot' ); ?>
							</p>
							<?php } else { ?>
								<?php /* translators: %s - Link for support */ ?>
							<p><?php echo wp_kses_post( sprintf( __( 'It appears the authorization process went wrong. Please try again by clicking the "Authenticate" button and make sure you authorize Snapshot to access your Dropbox. If you run into further issues, you can <a href="%s" target="_blank">contact our Support</a> team for help.', 'snapshot' ), Task\Backup\Fail::URL_CONTACT_SUPPORT ) ); ?>
							</p>
							<?php } ?>
						</div>
					</div>
				</div>

				<div role="alert" id="snapshot-correct-dropbox-reauth-creds" class="sui-notice sui-notice-success"
					aria-live="assertive" style="display: none;">
					<div class="sui-notice-content">
						<div class="sui-notice-message">
							<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
							<p><?php echo wp_kses_post( __( 'Snapshot has been successfully reauthorized in Dropbox.', 'snapshot' ) ); ?>
							</p>
						</div>
					</div>
				</div>

				<div role="alert" id="snapshot-reauth-dropbox-duplicate-destination-error" class="sui-notice sui-notice-success" aria-live="assertive" style="display: none;">
					<div class="sui-notice-content">
						<div class="sui-notice-message">
							<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
							<?php if ( Settings::get_branding_hide_doc_link() ) { ?>
								<p><?php esc_html_e( 'You\'re trying to save a destination that already exists. If you want to create a new destination with the same credentials, please choose a different directory. If you run into further issues, you can contact Support for help.', 'snapshot' ); ?></p>
							<?php } else { ?>
								<?php /* translators: %s - Link for support */ ?>
								<p><?php echo wp_kses_post( sprintf( __( 'You\'re trying to save a destination that already exists. If you want to create a new destination with the same credentials, please choose a different folder or create a new one. If you run into further issues, you can contact our <a href="%s" target="_blank">Support team</a> for help.', 'snapshot' ), 'https://wpmudev.com/hub2/support?utm_source=snapshot&utm_medium=email&utm_campaign=snapshot-email-get-support#get-support' ) ); ?></p>
							<?php } ?>
						</div>
					</div>
				</div>

				<div id="snapshot-dropbox-authorization-reauth" style="display: none;">
					<h4><?php echo esc_html( __( 'Dropbox authorization', 'snapshot' ) ); ?></h4>

					<div class="sui-border-frame">
						<form method="post" id="snapshot-test-dropbox-reauth-connection">
							<input type="hidden" name="tpd_action" value="generate_tokens">
							<input type="hidden" name="tpd_type" value="dropbox">
							<input type="hidden" name="tpd_auth_code" value="">
							<input type="hidden" name="tpd_save" value="0">
							<input type="hidden" id="tpd_dropbox_reauth_nonce"  name="tpd_dropbox_reauth_nonce" value="">
						</form>

						<p class="sui-description">
							<?php echo wp_kses_post( __( 'Connect with Dropbox integration by authenticating it using the button below. Note that you\'ll be taken to the Dropbox website to grant access to Snapshot and then redirected back.', 'snapshot' ) ); ?>
						</p>

						<a type="button" href="<?php echo esc_url( $auth_url ); ?>"
							class="sui-button sui-button-lg snapshot-connect-dropbox">
							<span aria-hidden="true" class="sui-icon-dropbox-connect"></span>
							<?php echo esc_html( __( 'Authenticate', 'snapshot' ) ); ?>
						</a>
					</div>
				</div>

				<form method="post" id="snapshot-dropbox-reauth-form-update" style="display: none;">
					<input type="hidden" name="tpd_action" value="update_destination">
					<input type="hidden" name="tpd_id">
					<input type="hidden" name="tpd_type">
					<input type="hidden" name="tpd_email">
					<input type="hidden" name="tpd_accesskey">
					<input type="hidden" name="tpd_secretkey">
					<input type="hidden" name="tpd_item_id">
					<input type="hidden" name="tpd_drive_id">
					<input type="hidden" name="tpd_name">
					<input type="hidden" name="tpd_path">
					<input type="hidden" name="tpd_limit">
				</form>

			</div>

			<div class="sui-box-footer sui-flatten sui-lg sui-content-separated">
				<button class="sui-button sui-button-blue" style="display: none;"
					id="snapshot-submit-dropbox-generate-tokens-reauth">
					<span class="sui-button-text-default">
						<?php esc_html_e( 'Finish', 'snapshot' ); ?>
					</span>

					<span class="sui-button-text-onload">
						<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
						<?php esc_html_e( 'Finishing...', 'snapshot' ); ?>
					</span>
				</button>
			</div>

		</div>
	</div>
</div>