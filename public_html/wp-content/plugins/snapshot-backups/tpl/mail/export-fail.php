<?php // phpcs:ignore
/**
 * Failed backup mail template
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
											<h1><?php echo wp_kses_post( sprintf( __( 'Backup export failed for <a href="%1$s" target="_blank">%2$s</a>.', 'snapshot' ), $site_url, $site ) ); ?>
											</h1>
											<?php /* translators: %s - User name */ ?>
											<p><?php echo esc_html( sprintf( __( 'Hi %s,', 'snapshot' ), $name ) ); ?>
											</p>
											<p><?php echo wp_kses_post( $p1_html ); ?></p>
											<p class="snapshot-log-error">
												<strong><?php esc_html_e( 'Backup Error Log', 'snapshot' ); ?></strong>
											</p>
											<p style="font-size: 13px; margin-top: 0px; padding-left: 30px;">
												<?php echo esc_html( $error1 . ' ' . $error2 ); ?></p>
											<div
												style="border-bottom: 1px solid #E6E6E6; margin: 0 -25px 30px;">
											</div>
											<p><?php echo wp_kses_post( $p2_html ); ?></p>
											<center><a class="button"
													href="<?php echo esc_attr( $button_link ); ?>"><?php echo esc_html( $button_text ); ?></a>
											</center>
											<?php if ( ! empty( $bottom_link ) && ! empty( $bottom_link_text ) ) { ?>
											<center><a style="font-size: 16px;"
													href="<?php echo esc_attr( $bottom_link ); ?>"><?php echo esc_html( $bottom_link_text ); ?></a>
											</center>
											<?php } ?>
											<?php if ( ! $is_branding_hidden ) { ?>
											<p><?php esc_html_e( 'Stay protected,', 'snapshot' ); ?></p>
											<p><?php esc_html_e( 'Snapshot', 'snapshot' ); ?></p>
											<p>
												<?php
												/* translators: $s - plugin custom name */
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