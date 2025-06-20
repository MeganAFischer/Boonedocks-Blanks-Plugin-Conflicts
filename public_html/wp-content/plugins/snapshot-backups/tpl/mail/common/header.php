<?php
/**
 * Email header template.
 *
 * @package snapshot
 */
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
	<title><?php echo esc_html( $subject ); ?></title>
	<!--[if !mso]><!-->
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<!--<![endif]-->
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="preconnect" href="https://fonts.bunny.net">
	<link href="https://fonts.bunny.net/css?family=roboto:400,700" rel="stylesheet" />
	<style type="text/css">
		@import url(https://fonts.bunny.net/css?family=roboto:400,700);
		* {
			-webkit-font-smoothing: antialiased;
			-moz-osx-font-smoothing: grayscale;
		}
		#outlook a {
			padding: 0;
		}
		body {
			margin: 0;
			padding: 0;
			-webkit-text-size-adjust: 100%;
			-ms-text-size-adjust: 100%;
		}
		table, td {
			border-collapse: collapse;
			mso-table-lspace: 0pt;
			mso-table-rspace: 0pt;
		}
		img {
			border: 0;
			height: auto;
			line-height: 100%;
			outline: none;
			text-decoration: none;
			-ms-interpolation-mode: bicubic;
		}
		p {
			display: block;
			margin: 13px 0;
		}

		@media only screen and (min-width:480px) {
			.mj-column-per-100 {
				width: 100% !important;
				max-width: 100%;
			}
		}
		@media only screen and (max-width:480px) {
			table.mj-full-width-mobile {
				width: 100% !important;
			}
			td.mj-full-width-mobile {
				width: auto !important;
			}
		}
		.p-30 {
			margin-bottom: 30px !important;
		}
		h1 {
			font-size: 25px;
			line-height: 35px;
		}
		h2 {
			font-size: 20px;
			line-height: 30px;
		}
		p, li {
			font-size: 14px;
			line-height: 30px;
		}
		p.snapshot-log-error {
			margin-bottom: 0px;
			padding-left: 30px;
			background-image: url("<?php echo esc_attr( $assets->get_asset( 'img/mail-icon-error-6px@2x.png' ) ); ?>");
			background-repeat: no-repeat;
			background-size: 16px 22px;
		}
		a {
			text-decoration: none !important;
			font-weight: 700 !important;
			color: #0059FF !important;
		}
		.hidden-img img {
			display: none !important;
		}
		.button a, a.button, a.button-cta {
			font-family: Roboto, arial, sans-serif;
			font-size: 13px !important;
			line-height: 24px;
			font-weight: bold;
			background: #0059FF;
			text-decoration: none !important;
			padding: 8px 21px;
			color: #ffffff !important;
			border-radius: 6px;
			display: inline-block;
			margin: 20px auto;
			text-transform: unset !important;
			min-width: unset !important;
		}
		small { font-size: 10px; line-height: 24px;}

		.main-content img { max-width: 100% !important; }

		@media (min-width: 600px) {
			p,li {
				font-size: 16px;
			}
		}
	</style>
	<!--[if mso]>
		<xml>
		<o:OfficeDocumentSettings>
			<o:AllowPNG/>
			<o:PixelsPerInch>96</o:PixelsPerInch>
		</o:OfficeDocumentSettings>
		</xml>
		<![endif]-->
	<!--[if lte mso 11]>
		<style type="text/css">
			.mj-outlook-group-fix { width:100% !important; }
		</style>
		<![endif]-->
	<!--[if !mso]><!-->
</head>
<body style="word-spacing:normal;background-color:#F6F6F6;">
	<div style="background-color:#F6F6F6;">
		<?php if ( ! $is_branding_hidden ) : ?>
			<!-- Header image -->
			<!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]-->
			<div style="margin:0px auto;max-width:600px;">
				<table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
					<tbody>
						<tr>
							<td style="direction:ltr;font-size:0px;padding:25px 0 0;text-align:center;">
								<div class="mj-column-per-100 mj-outlook-group-fix" style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
									<table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%">
										<tbody>
											<tr>
												<td style="background-color:#35104C;border-radius:15px 15px 0 0;vertical-align:top;padding:35px 0;">
													<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="" width="100%">
														<tbody>
															<tr>
																<td align="center" style="font-size:0px;padding:2px 25px;word-break:break-word;">
																	<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border-spacing:0px;">
																		<tbody>
																			<tr>
																				<td style="width:159px;">
																					<img height="auto" src="<?php echo esc_attr( $assets->get_asset( 'img/mail-snapshot-backup-logo.png' ) ); ?>" style="border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;font-size:13px;" width="159" />
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
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<!--[if mso | IE]></td></tr></table><![endif]-->
			<!-- END Header image -->
		<?php endif; ?>