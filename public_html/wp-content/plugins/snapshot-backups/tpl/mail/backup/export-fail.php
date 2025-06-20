<?php // phpcs:ignore
/**
 * Failed backup mail template.
 *
 * @var array $storage_info
 *
 * @package snapshot
 */

$border_radius = $is_branding_hidden ? 'border-radius: 15px;' : '';
$unit          = __( 'GB', 'snapshot' );
//phpcs:disable
?>
<!-- Main content -->
<!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" class="main-content-outlook" style="width:600px;" width="600" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]-->
<div class="main-content"
	style="background:#ffffff;background-color:#ffffff;margin:0px auto;max-width:600px;<?php echo esc_html( $border_radius ); ?>">
	<table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
		style="background:#ffffff;background-color:#ffffff;width:100%;<?php echo esc_html( $border_radius ); ?>">
		<tbody>
			<tr>
				<td style="direction:ltr;font-size:0px;padding:30px 25px 15px;text-align:center;">
					<!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td class="" style="vertical-align:top;width:550px;" ><![endif]-->
					<div class="mj-column-per-100 mj-outlook-group-fix"
						style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
						<table border="0" cellpadding="0" cellspacing="0" role="presentation"
							style="vertical-align:top;" width="100%">
							<tbody>
								<tr>
									<td align="left" style="font-size:0px;padding:0;word-break:break-word;">
										<div
											style="font-family:Roboto, Arial, sans-serif;font-size:18px;letter-spacing:-.25px;line-height:30px;text-align:left;color:#1A1A1A;">
											<?php /* translators: %1$s - Backup destination %1$s - Site URL, %2$s - Site domain */ ?>
											<h1><?php echo wp_kses_post( sprintf( __( 'Backup export to <strong>%1$s</strong> failed for <a href="%2$s" target="_blank">%3$s</a>.', 'snapshot' ), $destination, $site_url, $site ) ); ?>
											</h1>
											<?php /* translators: %s - User name */ ?>
											<p><?php echo esc_html( sprintf( __( 'Hi %s,', 'snapshot' ), $name ) ); ?>
											</p>

											<p><?php
												/** translators: %1$s - Site Name, %2$s - Date, %3$s - Destination name */
												echo sprintf(
												__( 'The backup for <strong>%1$s</strong> completed successfully on <strong>%2$s</strong>, but we encountered an issue while exporting it to <strong>%3$s</strong>.The following error message is listed in the Snapshot log:', 'snapshot' ),
												$site,
												$date,
												$destination,
											); ?></p>
											<p class="snapshot-log-error" style="margin-top: 30px">
												<strong><?php esc_html_e( 'Backup Error Log', 'snapshot' ); ?></strong>
											</p>
											<p style="font-size: 13px; margin-top: 0px; padding-left: 30px;">
												<?php echo wp_kses_post( $service_error ); ?></p>
											<div
												style="border-bottom: 1px solid #E6E6E6; margin: 0 -25px 30px;">
											</div>

											<div style="margin-top: 30px;">
												<?php if ( $is_branding_hidden ): ?>
													<p><?php echo sprintf(
														__( 'Please verify your <strong>%1$s</strong> connection settings, check available storage space in your %1$s account and run another backup. Or contact our support team if the issue persists.', 'snapshot' ),
														$destination,
													); ?></p>
												<?php else: ?>
													<p><?php echo sprintf(
														__( 'Please verify your <strong>%1$s</strong> connection settings, check available storage space in your %1$s account and run another backup. Or contact our <a href="%s" target="_blank">support team</a> if the issue persists.', 'snapshot' ),
														$destination,
														'https://wpmudev.com/hub2/support?utm_source=snapshot&utm_medium=email&utm_campaign=snapshot-email-get-support#get-support'
													); ?></p>
												<?php endif; ?>
											</div>

											<?php if ( ! $is_branding_hidden ) { ?>
											<div style="margin-top: 20px;">
												<p><?php esc_html_e( 'Stay protected,', 'snapshot' ); ?></p>
												<p><?php esc_html_e( 'Snapshot', 'snapshot' ); ?></p>
												<p>
													<?php
													/* translators: %s - plugin custom name */
													echo esc_html( sprintf( __( '%s Backup Hero', 'snapshot' ), $plugin_custom_name ) );
													?>
												</p>
											</div>
											<?php } ?>
										</div>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
					<!--[if mso | IE]></td></tr></table><![endif]-->
				</td>
			</tr>
		</tbody>
	</table>
</div>
<!--[if mso | IE]></td></tr></table><![endif]-->
<!-- END Main content -->