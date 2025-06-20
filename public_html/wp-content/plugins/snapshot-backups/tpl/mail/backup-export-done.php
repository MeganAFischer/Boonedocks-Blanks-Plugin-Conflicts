<?php // phpcs:ignore
/**
 * Backup export mail template.
 *
 * @package snapshot
 */
$restore_guide_url = 'https://wpmudev.com/docs/hub-2-0/backup/?utm_source=snapshot&utm_medium=email&utm_campaign=snapshot-documentation#restore-website-snapshot';
?>
<!-- Main content -->
<!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" class="main-content-outlook" style="width:600px;" width="600" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]-->
<div class="main-content" style="background:#ffffff;background-color:#ffffff;margin:0px auto;max-width:600px;
<?php
if ( $is_branding_hidden ) {
	?>
border-radius:15px;<?php } ?>">
	<table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:#ffffff;background-color:#ffffff;width:100%;
<?php
if ( $is_branding_hidden ) {
	?>
	border-radius:15px;<?php } ?>">
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
											<?php /* translators: %1$s - Site URL, %2$s - Site domain */ ?>
											<h1><?php echo wp_kses_post( sprintf( __( 'Backup of <a href="%1$s" target="_blank">%2$s</a> is ready for download.', 'snapshot' ), $site_url, $site ) ); ?>
											</h1>
											<?php /* translators: %s - User name */ ?>
											<p><?php echo esc_html( sprintf( __( 'Hi %s,', 'snapshot' ), $name ) ); ?>
											</p>
											<?php /* translators: %s - Backup URL */ ?>
											<p><?php echo wp_kses_post( sprintf( __( 'Your exported <a href="%s" target="_blank">backup</a> can be downloaded using the link below. This link will expire in 7 days, but don\'t worry, your backup will be kept for 50 days from the date it was created.', 'snapshot' ), $backup_url ) ); ?>
											</p>

											<?php if ( ! empty( $snapshot_name ) && ! empty( $export_date ) ) : ?>
											<table role="presentation" cellpadding="0" cellspacing="0"
												style="border: 1px solid #f2f2f2; width: 100%; font-size: 15px; margin: 23px 0;">
												<thead>
													<tr
														style="height: 28px; background: #f2f2f2; border-radius: 4px 4px 0px 0px; padding: 7px 20px;">
														<th
															style="width: 60%; padding: 3px 20px; font-weight: 500;">
															<?php echo esc_html( __( 'Backup title', 'snapshot' ) ); ?>
														</th>
														<th
															style="width: 40%; padding: 3px 20px; font-weight: 500;">
															<?php echo esc_html( __( 'Date Created', 'snapshot' ) ); ?>
														</th>
													</tr>
												</thead>
												<tbody>
													<tr style="height: 56px;">
														<td style="padding: 18px 0 20px 20px;"><strong
																style="font-weight: 500"><?php echo esc_html( $snapshot_name ); ?></strong>
														</td>
														<td style="padding: 18px 0 20px 20px;">
															<?php echo esc_html( $export_date ); ?></td>
													</tr>
												</tbody>
											</table>
											<?php endif; ?>

											<center><a class="button"
													href="<?php echo esc_attr( $export_link ); ?>"><?php esc_html_e( 'Download Backup', 'snapshot' ); ?></a>
											</center>

											<p><?php echo wp_kses_post( __( 'You can also use the link below to download the backup:', 'snapshot' ) ); ?>
											</p>
											<a
												href="<?php echo esc_attr( $export_link ); ?>"><?php echo esc_html( $export_link ); ?></a>
											<h1><?php esc_html_e( 'How to restore your site:', 'snapshot' ); ?>
											</h1>
											<ol>
												<li>
													<p><?php esc_html_e( 'Download the backup .zip file using the link above.', 'snapshot' ); ?>
													</p>
												</li>
												<li>
													<p>
														<?php
														/* translators: %s - Snapshot Installer URL */
														echo wp_kses_post( sprintf( __( 'Download the <a href="%s" target="_blank">snapshot-installer.php</a> file.', 'snapshot' ), esc_attr( $snapshot_installer_url ) ) );
														?>
													</p>
												</li>
												<li>
													<p><?php echo wp_kses_post( __( 'Upload both the backup <strong>.zip</strong> file and the <strong>snapshot-installer.php</strong> file to the root directory of the site to which you’d like to restore the backup.', 'snapshot' ) ); ?>
													</p>
												</li>
												<?php $snapshot_installer_path = $site_url . '/snapshot-installer.php'; ?>
												<li>
													<p>
														<?php
														/* translators: %1$s - Snapshot Installer path, %2$s - Snapshot Installer path */
														echo wp_kses_post( sprintf( __( 'Navigate to the <strong>snapshot-installer.php</strong> file in your web browser (<a href="%1$s" target="_blank">%1$s</a>) and follow the on-screen steps to complete the restore process.', 'snapshot' ), esc_attr( $snapshot_installer_path ), $snapshot_installer_path ) );
														?>
													</p>
												</li>
											</ol>

											<?php if ( ! $is_branding_hidden ) { ?>
												<p class="p-30">
													<?php
													/* translators: %s - Restore guide URL */
													echo wp_kses_post( sprintf( __( 'For more detailed instructions, check out our <a href="%s" target="_blank">restore guide</a>.', 'snapshot' ), esc_attr( $restore_guide_url ) ) );
													?>
												</p>
											<?php } ?>
											<p><?php esc_html_e( 'Stay protected,', 'snapshot' ); ?></p>
											<?php if ( 'WPMU DEV' === $plugin_custom_name ) { ?>
												<p><?php esc_html_e( 'Snapshot', 'snapshot' ); ?></p>
											<?php } ?>
											<p>
												<?php
												/* translators: %s - Plugin custom name */
												echo esc_html( sprintf( __( '%s Backup Hero', 'snapshot' ), $plugin_custom_name ) );
												?>
											</p>
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