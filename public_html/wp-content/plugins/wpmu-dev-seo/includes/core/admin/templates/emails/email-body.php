<?php
/**
 * Template: Email body.
 *
 * @package SmartCrawl
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

$email_template      = empty( $email_template ) ? '' : $email_template;
$email_template_args = empty( $email_template_args ) ? array() : $email_template_args;
?>

<body style="-moz-box-sizing: border-box; -ms-text-size-adjust: 100%; -webkit-box-sizing: border-box; -webkit-text-size-adjust: 100%; background-color: #F6F6F6; box-sizing: border-box; color: #1A1A1A; color: #1A1A1A'Roboto', Arial, sans-serif; font-size: 15px; font-weight: normal; line-height: 26px; margin: 0; min-width: 100%; padding: 0; text-align: left; width: 100% !important;">
<table
	class="body"
	style="background-color: #F6F6F6; border-collapse: collapse; border-spacing: 0; color: #1A1A1A; font-family: 'Roboto', Arial, sans-serif; font-size: 15px; font-weight: normal; height: 100%; line-height: 26px; margin: 0; padding: 0; text-align: left; vertical-align: top; width: 100%;"
>
	<tbody>
	<tr style="padding: 0; text-align: left; vertical-align: top;">
		<td
			class="center" align="center" valign="top"
			style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; color: #1A1A1A; font-family: 'Roboto', Arial, sans-serif; font-size: 15px; font-weight: normal; hyphens: auto; line-height: 26px; margin: 0; padding: 0; text-align: left; vertical-align: top; word-wrap: break-word;"
		>
			<center style="min-width: 600px; width: 100%;">
				<table
					class="container"
					style="background-color: #fff; border-collapse: collapse; border-spacing: 0; margin: 0 auto; padding: 0; text-align: inherit; vertical-align: top; width: 600px;"
				>
					<tbody>
					<tr style="padding: 0; text-align: left; vertical-align: top;">
						<td style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; color: #1A1A1A; font-family: 'Roboto', Arial, sans-serif; font-size: 15px; font-weight: normal; hyphens: auto; line-height: 26px; margin: 0; padding: 0; text-align: left; vertical-align: top; word-wrap: break-word;">
							<?php $this->render_view( 'emails/email-header' ); ?>
							<?php $this->render_view( $email_template, $email_template_args ); ?>
							<?php $this->render_view( 'emails/email-footer' ); ?>
						</td>
					</tr>
					</tbody>
				</table>
			</center>
		</td>
	</tr>
	</tbody>
</table>
</body>