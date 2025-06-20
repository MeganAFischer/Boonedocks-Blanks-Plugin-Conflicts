<?php
/**
 * Template: Email Footer.
 *
 * @package SmartCrawl
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$plugin_url    = untrailingslashit( SMARTCRAWL_PLUGIN_URL );
$logo_url      = sprintf( '%s/assets/images/graphic-email-footer.png', $plugin_url );
$icon_fb       = sprintf( '%s/assets/images/icon-facebook.png', $plugin_url );
$icon_x        = sprintf( '%s/assets/images/icon-x.png', $plugin_url );
$icon_ig       = sprintf( '%s/assets/images/icon-instagram.png', $plugin_url );
$hide_branding = \SmartCrawl\Controllers\White_Label::get()->is_hide_wpmudev_branding();
?>

<table
	class="wrapper logo-bottom" align="center"
	style="background-color: #F6F6F6; border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%;"
>
	<tbody>
	<?php if ( $hide_branding ) : ?>
		<tr>
			<td style="padding: 30px;"></td>
		</tr>
	<?php else : ?>
		<tr style="padding: 0; text-align: left; vertical-align: top;">
			<td
				class="wrapper-inner logo-bottom-inner" align="center"
				style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; color: #1A1A1A; font-family: 'Roboto', Arial, sans-serif; font-size: 14px; font-weight: normal; hyphens: auto; line-height: 1em; margin: 0; padding: 29px 0; text-align: center; vertical-align: top; word-wrap: break-word;background: #E7F1FB;border-radius: 0 0 15px 15px;">
				<a
					href="https://premium.wpmudev.org" target="_blank" class="logo-link"
					style="color: #1A1A1A; display: inline-block; font-family: 'Roboto', Arial, sans-serif; font-weight: normal; line-height: 1.3; margin: 0; padding: 0; text-align: left; text-decoration: none;">
					<img
						src="<?php echo esc_url( $logo_url ); ?>"
						alt="<?php esc_attr_e( 'WPMU DEV', 'wds' ); ?>"
						style="-ms-interpolation-mode: bicubic; border: none; clear: both; display: block; max-width: 100%; outline: none; text-decoration: none; width: 168px; height: auto;" width="168" height="auto"/>
				</a>
			</td>
		</tr>
		<tr style="padding: 0; text-align: left; vertical-align: top;">
			<td
				class="wrapper-inner footer-inner"
				style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; color: #1A1A1A; font-family: 'Roboto', Arial, sans-serif; font-size: 14px; font-weight: normal; hyphens: auto; line-height: 30px; margin: 0; padding: 25px 20px 15px; text-align: center; vertical-align: top; word-wrap: break-word;">

				<table
					class="footer-content" align="center"
					style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%;">
					<tbody>
					<tr style="padding: 0; text-align: left; vertical-align: top;">
						<td
							class="footer-content-inner"
							style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; font-size: 0; line-height: 0; margin: 0; padding: 0; text-align: center; vertical-align: top; word-wrap: break-word;" align="center">
							<table
								align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
								style="float:none;display:inline-table;">
								<tbody>
								<tr>
									<td style="vertical-align:middle;">
										<span
											style="color: #000000;font-size:13px;font-weight:700;font-family:Roboto, Arial, sans-serif;line-height:25px;text-decoration:none;">Follow us </span>
									</td>
								</tr>
								</tbody>
							</table>
							<table
								align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
								style="float:none;display:inline-table;">
								<tbody>
								<tr>
									<td style="padding:1px;vertical-align:middle;">
										<table
											border="0" cellpadding="0" cellspacing="0" role="presentation"
											style="background:transparent;border-radius:3px;width:25px;">
											<tbody>
											<tr>
												<td style="font-size:0;height:25px;vertical-align:middle;width:25px;">
													<a href="https://www.facebook.com/wpmudev" target="_blank">
														<img
															height="25" src="<?php echo esc_url( $icon_fb ); ?>"
															style="border-radius:3px;display:block;" width="25">
													</a>
												</td>
											</tr>
											</tbody>
										</table>
									</td>
								</tr>
								</tbody>
							</table>
							<table
								align="center"
								border="0"
								cellpadding="0"
								cellspacing="0"
								role="presentation"
								style="float:none;display:inline-table;">
								<tbody>
								<tr>
									<td style="padding:1px;vertical-align:middle;">
										<table
											border="0" cellpadding="0" cellspacing="0" role="presentation"
											style="background:transparent;border-radius:3px;width:25px;">
											<tbody>
											<tr>
												<td style="font-size:0;height:25px;vertical-align:middle;width:25px;">
													<a href="https://www.instagram.com/wpmu_dev/" target="_blank">
														<img
															height="25" src="<?php echo esc_url( $icon_ig ); ?>"
															style="border-radius:3px;display:block;" width="25">
													</a>
												</td>
											</tr>
											</tbody>
										</table>
									</td>
								</tr>
								</tbody>
							</table>
							<table
								align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
								style="float:none;display:inline-table;">
								<tbody>
								<tr>
									<td style="padding:1px;vertical-align:middle;">
										<table
											border="0" cellpadding="0" cellspacing="0" role="presentation"
											style="background:transparent;border-radius:3px;width:25px;">
											<tbody>
											<tr>
												<td style="font-size:0;height:25px;vertical-align:middle;width:25px;">
													<a href="https://twitter.com/wpmudev" target="_blank">
														<img
															height="25" src="<?php echo esc_url( $icon_x ); ?>"
															style="border-radius:3px;display:block;" width="25">
													</a>
												</td>
											</tr>
											</tbody>
										</table>
									</td>
								</tr>
								</tbody>
							</table>
						</td>
					</tr>
					</tbody>
				</table>

			</td>
		</tr>
		<tr style="padding: 0; text-align: left; vertical-align: top;">
			<td
				class="wrapper-inner address-inner"
				style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; color: #1A1A1A; font-family: 'Roboto', Arial, sans-serif; font-size: 14px; font-weight: normal; hyphens: auto; line-height: 30px; margin: 0; padding: 0 20px 40px; text-align: center; vertical-align: top; word-wrap: break-word;">

				<table
					class="address-content" align="center"
					style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%;">
					<tbody>
					<tr style="padding: 0; text-align: left; vertical-align: top;">
						<td
							class="address-content-inner"
							style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; color: #AAAAAA; font-family: 'Roboto', Arial, sans-serif; font-size: 10px; font-weight: normal; hyphens: auto; line-height: 30px; margin: 0; padding: 0; text-align: left; vertical-align: top; word-wrap: break-word;">
							<p style="color: #505050; font-family: 'Roboto', Arial, sans-serif; font-size: 10px; font-weight: normal; letter-spacing: -0.25px; line-height: 30px; margin: 0; padding: 0; text-align: center; text-transform: uppercase;"><?php esc_html_e( 'Incsub, PO Box 163 Albert Park, Victoria, 3206, Australia.', 'wds' ); ?></p>
						</td>
					</tr>
					</tbody>
				</table>

			</td>
		</tr>
	<?php endif; ?>
	</tbody>
</table>