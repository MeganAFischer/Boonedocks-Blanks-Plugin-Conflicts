<?php
/**
 * Template: Email Header.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hide_branding       = \SmartCrawl\Controllers\White_Label::get()->is_hide_wpmudev_branding();
$plugin_url          = untrailingslashit( SMARTCRAWL_PLUGIN_URL );
$header_image_url    = sprintf( '%s/assets/images/graphic-seo-report.png', $plugin_url );
$header_image_url_2x = sprintf( '%s/assets/images/graphic-seo-report@2x.png', $plugin_url );
$alt_text            = sprintf(
	/* translators: %s: plugin title */
	__( '%s Report', 'wds' ),
	\smartcrawl_get_plugin_title()
);
?>

<?php // phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet ?>
<link href="https://fonts.bunny.net/css?family=Roboto:wght@400;700&amp;display=swap" rel="stylesheet" type="text/css">
<table
	class="wrapper hero" align="left"
	style="background-color: #F6F6F6; border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%;"
>
	<tbody>
	<tr style="padding: 0; text-align: left; vertical-align: top;">
		<td
			class="wrapper-inner hero-inner"
			style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; color: #1A1A1A; font-family: 'Roboto', Arial, sans-serif; font-size: 15px; font-weight: normal; hyphens: auto; line-height: 26px; margin: 0; padding: 20px 0 0; text-align: left; vertical-align: top; word-wrap: break-word;"
		>
			<table
				class="hero-content" align="left"
				style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%;"
			>
				<tbody>
				<tr style="padding: 0; text-align: center; vertical-align: bottom;">
					<?php if ( ! $hide_branding ) : ?>
						<td
							class="hero-image"
							style="background-color: #de240a; border-radius: 15px 15px 0 0; height: 100px; border-collapse: collapse !important; margin: 0; padding: 0; text-align: center; vertical-align: middle;"
						>
							<img
								src="<?php echo esc_url( $header_image_url_2x ); ?>"
								srcset="<?php echo esc_url( $header_image_url ); ?>, <?php echo esc_url( $header_image_url_2x ); ?> 2x"
								alt="<?php echo esc_attr( $alt_text ); ?>"
								style="-ms-interpolation-mode: bicubic; border: none; vertical-align:bottom; clear: both; display: inline-block; outline: none; text-decoration: none; width: 141px; height: auto"
								width="141"
							>
						</td>
					<?php endif; ?>
				</tr>
				</tbody>
			</table>
		</td>
	</tr>
	</tbody>
</table>