<?php
/**
 * Template: Term Google Preview.
 *
 * @package Smartcrwal
 */

namespace SmartCrawl;

use SmartCrawl\Cache\Term_Cache;

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
$term = empty( $term ) ? null : $term;

if ( ! $term ) {
	return;
}

$link            = get_term_link( $term );
$smartcrawl_term = Term_Cache::get()->get_term( $term->term_id );
if ( ! $smartcrawl_term ) {
	return;
}

$title       = $smartcrawl_term->get_meta_title();
$description = $smartcrawl_term->get_meta_description();
?>
<div class="wds-metabox-preview">
	<label class="sui-label"><?php esc_html_e( 'Google Preview', 'wds' ); ?></label>

	<?php
	$this->render_view(
		'onpage/onpage-preview',
		array(
			'link'        => esc_url( $link ),
			'title'       => esc_html( $title ),
			'description' => esc_html( $description ),
		)
	);
	?>
</div>