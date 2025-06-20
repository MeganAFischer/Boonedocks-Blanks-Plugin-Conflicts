<?php
/**
 * Template: Term SEO Tab.
 *
 * @package Smartcrwal
 */

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
$tax_meta  = empty( $tax_meta ) ? array() : $tax_meta;
$term      = empty( $term ) ? null : $term;
$is_active = empty( $is_active ) ? false : $is_active;
$title_key = empty( $title_key ) ? '' : $title_key;
$desc_key  = empty( $desc_key ) ? '' : $desc_key;
?>
<div class="<?php echo $is_active ? 'active' : ''; ?>">
	<div class="wds-metabox-section">
		<?php $this->render_view( 'metabox/metabox-dummy-preview' ); ?>
		<?php
		$this->render_view(
			'term/term-meta-edit-form',
			array(
				'tax_meta'  => $tax_meta,
				'term'      => $term,
				'title_key' => $title_key,
				'desc_key'  => $desc_key,
			)
		);
		?>
	</div>
</div>