<?php
/**
 * Dashboard Welcome Modal.
 *
 * @package SmartCrawl
 */

use SmartCrawl\Settings;

$modal_id = 'wds-welcome-modal';

$options = Settings::get_specific_options( 'wds_settings_options' );
?>

<div class="sui-modal sui-modal-md">
	<div
		role="dialog"
		id="<?php echo esc_attr( $modal_id ); ?>"
		class="sui-modal-content <?php echo esc_attr( $modal_id ); ?>-dialog"
		aria-modal="true"
		aria-labelledby="<?php echo esc_attr( $modal_id ); ?>-dialog-title"
		aria-describedby="<?php echo esc_attr( $modal_id ); ?>-dialog-description">

		<div class="sui-box" role="document">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--40">
				<div class="sui-box-banner" role="banner" aria-hidden="true">
					<?php /* translators: %s: plugin title */ ?>
					<img src="<?php echo esc_attr( SMARTCRAWL_PLUGIN_URL ); ?>assets/images/upgrade-welcome-header.svg" alt="<?php printf( esc_html__( '%s works with other SEO Plugins.', 'wds' ), esc_attr( \smartcrawl_get_plugin_title() ) ); ?>"/>
				</div>
				<button
					class="sui-button-icon sui-button-float--right" data-modal-close
					id="<?php echo esc_attr( $modal_id ); ?>-close-button"
					type="button"
				>
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Close this dialog window', 'wds' ); ?></span>
				</button>
				<h3 class="sui-box-title sui-lg" id="<?php echo esc_attr( $modal_id ); ?>-dialog-title">
					<?php
					printf(
						/* translators: 1,2: strong tag, 3: plugin title */
						esc_html__( '%1$s%3$s%2$s works with other SEO Plugins.', 'wds' ),
						'<strong>',
						'</strong>',
						esc_html( \smartcrawl_get_plugin_title() )
					);
					?>
				</h3>

				<div class="sui-box-body">
					<p class="sui-description" id="<?php echo esc_attr( $modal_id ); ?>-dialog-description">
						<?php
						$user = wp_get_current_user();

						printf(
							/* translators: %s: current user display name */
							esc_html__(
								'Hey there! %s, if are you tired of managing multiple SEO plugins, then say hello to SmartCrawl\'s new superpower: seamless compatibility with your favorite SEO plugins!',
								'wds'
							),
							esc_html( $user->display_name )
						);
						?>
					</p>
					<p class="sui-description" id="<?php echo esc_attr( $modal_id ); ?>-dialog-description">
						<?php
						esc_html_e(
							'You can now leverage SmartCrawl\'s powerful features alongside other SEO plugins without worrying about any conflicts. Simply navigate to the settings page and deactivate the modules that conflict with your current SEO plugin.',
							'wds'
						);
						?>
					</p>

					<button
						id="<?php echo esc_attr( $modal_id ); ?>-get-started"
						type="button"
						class="sui-button wds-disabled-during-request">
						<span class="sui-loading-text">
							<?php esc_html_e( 'Got it!', 'wds' ); ?>
						</span>
						<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
					</button>
				</div>
			</div>
		</div>
	</div>
</div>