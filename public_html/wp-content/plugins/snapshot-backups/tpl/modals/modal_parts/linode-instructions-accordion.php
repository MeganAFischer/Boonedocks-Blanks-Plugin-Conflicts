<?php // phpcs:ignore
/**
 * Accordion with instructions on how to get Linode credentials.
 *
 * @package snapshot
 */

?>
<div class="sui-accordion sui-accordion-flushed snapshot-linode-credentials-howto" style="display: none;">

	<div class="sui-accordion-item">

		<div class="sui-accordion-item-header">
			<div class="sui-accordion-item-title" style="flex: auto;"><span aria-hidden="true" class="sui-icon-warning-alert" style=" font-size: 12px; "></span>
			<?php esc_html_e( 'How to get Linode credentials?', 'snapshot' ); ?>
			</div>

			<div>
				<button class="sui-button-icon sui-accordion-open-indicator" aria-label="Open item"><span class="sui-icon-chevron-down" aria-hidden="true"></span></button>
			</div>
		</div>

		<div class="sui-accordion-item-body">
			<div class="sui-box">
				<div class="sui-box-body">
					<p class="sui-description"><strong><?php esc_html_e( 'Follow the instructions below to retrieve your Linode credentials.', 'snapshot' ); ?></strong></p>
						<ol style="margin-left: 15px;">
							<?php /* translators: %s - Link for Wasabi login */ ?>
							<li><?php echo wp_kses_post( sprintf( __( '<strong>Log in to your Linode account</strong> <br>Visit <a href="%s" target="_blank">login.linode.com/login</a>', 'snapshot' ), 'https://login.linode.com/login' ) ); ?></li>
							<li><?php echo wp_kses_post( __( '<strong>Navigate to Object Storage</strong> <br>Go to Storage > Object Storage in your Linode dashboard.', 'snapshot' ) ); ?></li>
							<li><?php echo wp_kses_post( __( '<strong>Create or Access a Bucket</strong> <br> If you already have a bucket, proceed to the next step.<br>If not, create a new bucket in your preferred region.', 'snapshot' ) ); ?></li>
							<li><?php echo wp_kses_post( __( '<strong>Generate Access Keys </strong><br>Go to the Access Keys tab. <br>Click Create a New Key. <br>Make sure to select the same region as your bucket. <br>After creation, you’ll receive an Access Key, Secret Key & the Endpoint.', 'snapshot' ) ); ?></li>
						</ol>
					</p>
				</div>

			</div>
		</div>

	</div>
</div>