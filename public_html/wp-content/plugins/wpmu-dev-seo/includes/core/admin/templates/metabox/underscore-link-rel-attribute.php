<?php
/**
 * Template: Anchor rel attribute template for underscore.
 *
 * @package Smartcrwal
 */

?>

<div class="wp-link-text-field wp-link-rel-attribute">
	<label>
		<span><?php esc_html_e( 'Rel Attributes', 'wds' ); ?></span>
		<input id="wp-link-rel-attributes" type="text" value=""/>
	</label>
	<p>
		<i><?php esc_html_e( "Possible values: 'nofollow', 'ugc', 'sponsored' separated by spaces", 'wds' ); ?></i>
	</p>
</div>