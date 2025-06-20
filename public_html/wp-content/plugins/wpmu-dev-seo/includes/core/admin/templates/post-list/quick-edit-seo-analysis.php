<?php
/**
 * Template for Focus Keywords section
 *
 * @package SmartCrawl
 */

?>

<fieldset class="inline-edit-col-left" style="clear:left">
	<div class="inline-edit-col long-label">
		<h4><?php echo esc_html( \smartcrawl_get_plugin_title() ); ?></h4>
		<label>
			<span class="title"><?php esc_html_e( 'Focus keyphrases', 'wds' ); ?></span>
			<span class="input-text-wrap">
				<input class="ptitle smartcrawl_focus" type="text" value="" name="wds_focus"/>
				<input
					type="hidden"
					value="<?php echo esc_attr( wp_create_nonce( 'wds-metabox-nonce' ) ); ?>"
					name="_wds_nonce"
				/>
			</span>
		</label>
	</div>
</fieldset>