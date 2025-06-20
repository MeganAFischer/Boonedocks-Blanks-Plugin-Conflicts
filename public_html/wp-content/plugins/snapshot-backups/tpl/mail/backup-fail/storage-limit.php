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
											<?php /* translators: %1$s - Site URL, %2$s - Site domain */ ?>
											<h1><?php echo wp_kses_post( sprintf( __( 'Backup failed for <a href="%1$s" target="_blank">%2$s</a>.', 'snapshot' ), $site_url, $site ) ); ?>
											</h1>
											<?php /* translators: %s - User name */ ?>
											<p><?php echo esc_html( sprintf( __( 'Hi %s,', 'snapshot' ), $name ) ); ?>
											</p>
											<p><?php echo wp_kses_post( $p1_html ); ?></p>
											<p class="snapshot-log-error" style="margin-top: 30px">
												<strong><?php esc_html_e( 'Backup Error Log', 'snapshot' ); ?></strong>
											</p>
											<p style="font-size: 13px; margin-top: 0px; padding-left: 30px;">
												<?php echo esc_html( $error1 . ' ' . $error2 ); ?></p>
											<div
												style="border-bottom: 1px solid #E6E6E6; margin: 0 -25px 30px;">
											</div>

											<?php if ( ! $is_branding_hidden ) : ?>
											<div
												style="background-color: rgba(40, 110, 250, 0.1); padding: 20px; border-radius: 8px;">
												<p
													style="font-size: 13px; line-height: 22px; letter-spacing: -0.25px; color: #333; margin-top: 0;">
													<?php esc_html_e( 'Get additional storage space in one click by upgrading your WPMU DEV storage plan. Thousands of our members schedule terabytes of data for automatic weekly backups.', 'snapshot' ); ?>
												</p>
												<a class="button" style="margin-top: 0; margin-bottom: 0;"
													href="<?php echo esc_url( $button_link ); ?>"><?php esc_html_e( 'Upgrade Storage', 'snapshot' ); ?></a>
											</div>
											<?php endif; ?>

											<div style="margin-top: 30px;">
												<h2
													style="font-size: 25px; color: #1a1a1a; line-height: 30px; font-style: normal; font-weight: 700;">
													<?php esc_html_e( 'Snapshot Storage Information', 'snapshot' ); ?>
												</h2>
												<p
													style="font-size: 14px; line-height: 22px; font-weight: 400; font-style: normal;">
													<?php esc_html_e( 'Here is your current Snapshot storage usage.', 'snapshot' ); ?>
												</p>

												<div
													style="background-color: #f7f7f7; padding: 30px; border-radius: 8px; margin-top: 20px;">
													<table width="100%">
														<tr>
															<td>
																<div>
																	<h4
																		style="padding: 0; margin: 0; font-size: 16px; color: #1a1a1a; line-height: 24px; font-weight: 700;">
																		<?php esc_html_e( 'Storage Used', 'snapshot' ); ?>
																	</h4>
																	<table>
																		<tr>
																			<td>
																				<p
																					style="font-size: 50px; line-height: 55px; font-weight: 700; color: #333;">
																					<?php echo esc_html( mb_to_gb( $storage_info['used_size'] ) ); ?>
																				</p>
																			</td>
																			<td>
																				<span
																					style="color: #666666; font-weight: 600; font-size: 15px; line-height: 22px; letter-spacing: -0.25px; display: inline-block; margin-top: 30px; margin-left: 5px;">
																					<?php echo esc_html( $unit ); ?>
																					/
																					<?php echo esc_html( mb_to_gb( $storage_info['storage_size'] ) ); ?><?php echo esc_html( $unit ); ?>
																				</span>
																			</td>
																		</tr>
																	</table>
																</div>
															</td>
															<td>&nbsp;</td>
															<td align="right">
																<a class="button"
																	style="margin-top: 0; margin-bottom: 0;"
																	href="<?php echo esc_url( $bottom_link ); ?>"><?php esc_html_e( 'Manage Storage', 'snapshot' ); ?></a>
															</td>
														</tr>
													</table>
												</div>
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