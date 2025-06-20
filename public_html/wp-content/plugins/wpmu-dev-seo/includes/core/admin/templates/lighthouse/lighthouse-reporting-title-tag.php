<?php
/**
 * Template: Lighthouse Reporting Title Tag.
 *
 * @package SmartCrawl
 */

$is_member = ! empty( $_view['is_member'] );

if ( $is_member ) {
	return;
}
?>

<span class="sui-tag sui-tag-pro"><?php esc_html_e( 'Pro', 'wds' ); ?></span>