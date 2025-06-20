<?php
/**
 * Template: Social Twitter Embed.
 *
 * @package Smartcrwal
 */

$tweet_url = empty( $tweet_url ) ? '' : esc_url( $tweet_url );
$large     = empty( $large ) ? false : $large;

if ( ! $tweet_url ) {
	return;
}
?>
<div class="wds-twitter-embed <?php echo $large ? 'wds-twitter-embed-large' : ''; ?>">
	<?php
	global $wp_embed;
	/**
	 * Embed.
	 *
	 * @var WP_Embed $wp_embed
	 */
	// $tweet_url has been escaped above so it's safe to output and the embed won't work if escaped
	echo $wp_embed->autoembed( $tweet_url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
</div>