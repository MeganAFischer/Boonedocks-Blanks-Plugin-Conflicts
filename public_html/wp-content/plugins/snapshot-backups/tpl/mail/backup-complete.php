<?php
/**
 * Mail template for backup complete.
 *
 * @package snapshot
 */
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
											<h1><?php echo wp_kses_post( sprintf( __( 'Backup successful for <a href="%1$s" target="_blank">%2$s</a>.', 'snapshot' ), $site_url, $site ) ); ?>
											</h1>
											<?php /* translators: %s - User name */ ?>
											<p><?php echo esc_html( sprintf( __( 'Hi %s,', 'snapshot' ), $name ) ); ?>
											</p>
											<p>
											<?php
												echo wp_kses_post(
													sprintf(
														/* translators: %1$s - Site URL, %2$s - Site domain */
														__( 'The backup for <a href="%1$s" target="_blank">%2$s</a> was created and stored successfully. Backups are retained for up to 50 days, or until the storage limit has been reached.', 'snapshot' ),
														$site_url,
														$site
													)
												);
												?>
											</p>
											<center><a class="button"
													href="<?php echo esc_attr( $backup_url ); ?>"><?php esc_html_e( 'View Backup', 'snapshot' ); ?></a>
											</center>
											<?php if ( ! $is_branding_hidden ) { ?>
											<p><?php esc_html_e( 'Stay protected,', 'snapshot' ); ?></p>
											<p><?php esc_html_e( 'Snapshot', 'snapshot' ); ?></p>
											<p>
												<?php
												/* translators: %s - Plugin name */
												echo esc_html( sprintf( __( '%s Backup Hero', 'snapshot' ), $plugin_custom_name ) );
												?>
											</p>
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