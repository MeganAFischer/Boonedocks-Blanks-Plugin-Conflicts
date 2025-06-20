<?php
/**
 * Template: Notice.
 *
 * @package SmartCrawl
 */

$message = empty( $message ) ? '' : $message;
$class   = empty( $class ) ? 'sui-notice-warning' : $class;
$loading = ! empty( $loading );
?>
<div class="wds-notice sui-notice <?php echo esc_attr( $class ); ?>">
	<div class="sui-notice-content">
		<div class="sui-notice-message">
			<?php if ( $loading ) : ?>
				<span class="sui-notice-icon sui-icon-loader sui-loading sui-md" aria-hidden="true"></span>
			<?php else : ?>
				<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
			<?php endif; ?>
			<p><?php echo wp_kses_post( $message ); ?></p>
		</div>
	</div>
</div>