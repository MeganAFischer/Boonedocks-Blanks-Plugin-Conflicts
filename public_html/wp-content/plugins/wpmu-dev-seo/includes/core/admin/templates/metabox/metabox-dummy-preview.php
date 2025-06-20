<?php
/**
 * Template: Metabox Dummy Preview.
 *
 * @package Smartcrwal
 */

?>

<div class="wds-metabox-preview">
	<label class="sui-label"><?php esc_html_e( 'Google Preview', 'wds' ); ?></label>
	<?php
	if ( apply_filters( 'smartcrawl_metabox_visible_parts_preview_area', true ) ) :
		$this->render_view( 'onpage/onpage-preview' );
	endif;
	?>
</div>