<?php
/**
 * Template: Term Meta Edit Form.
 *
 * @package Smartcrwal
 */

namespace SmartCrawl;

use SmartCrawl\Admin\Settings\Onpage;
use SmartCrawl\Cache\Term_Cache;

$tax_meta  = empty( $tax_meta ) ? array() : $tax_meta;
$term      = empty( $term ) ? null : $term; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$title_key = empty( $title_key ) ? '' : $title_key;
$desc_key  = empty( $desc_key ) ? '' : $desc_key;

if ( ! $term ) {
	return;
}
$smartcrawl_term = Term_Cache::get()->get_term( $term->term_id );
if ( ! $smartcrawl_term ) {
	return;
}

$meta_title = \smartcrawl_get_array_value( $tax_meta, 'wds_title' );

if ( ! $meta_title ) {
	$meta_title = $title_key;
}

$title_placeholder = $smartcrawl_term->get_meta_title();

$meta_desc = \smartcrawl_get_array_value( $tax_meta, 'wds_desc', '' );

if ( ! $meta_desc ) {
	$meta_desc = $desc_key;
}

$desc_placeholder = $smartcrawl_term->get_meta_description();

$macros = array_merge(
	Onpage::get_term_macros( $term->name ),
	Onpage::get_general_macros()
);
?>
<div class="wds-edit-meta">
	<a class="sui-button sui-button-ghost">
		<span class="sui-icon-pencil" aria-hidden="true"></span>

		<?php esc_html_e( 'Edit Meta', 'wds' ); ?>
	</a>

	<div class="sui-border-frame" style="display: none;">
		<div class="sui-notice sui-notice-inactive wds-notice">
			<div class="sui-notice-content">
				<div class="sui-notice-message">
					<span class="sui-notice-icon sui-md sui-icon-info" aria-hidden="true"></span>
					<p><?php esc_html_e( 'You need to save the updates to refresh preview.', 'wds' ); ?></p>
				</div>
			</div>
		</div>

		<div class="sui-form-field">
			<label class="sui-label" for="wds_title">
				<?php esc_html_e( 'SEO Title', 'wds' ); ?>
				<span>
					<?php
					echo esc_html(
						sprintf(
						/* translators: 1, 2: Min/max length */
							__( '- Minimum of %1$d characters, max %2$d.', 'wds' ),
							\smartcrawl_title_min_length(),
							\smartcrawl_title_max_length()
						)
					);
					?>
				</span>
			</label>

			<div class="sui-insert-variables wds-allow-macros">
				<input
					type="text"
					id="wds_title"
					name="wds_title"
					placeholder="<?php echo esc_attr( $title_placeholder ); ?>"
					value="<?php echo esc_attr( $meta_title ); ?>"
					class="sui-form-control wds-meta-field"
				/>
				<?php $this->render_view( 'macros-dropdown', array( 'macros' => $macros ) ); ?>
			</div>

			<p class="sui-description">
				<?php esc_html_e( 'The SEO title is used on the archive page for this term.', 'wds' ); ?>
			</p>
		</div>

		<div class="sui-form-field">
			<label class="sui-label" for="wds_metadesc">
				<?php esc_html_e( 'Description', 'wds' ); ?>
				<span>
					<?php
					echo esc_html(
						sprintf(
						/* translators: 1, 2: Min/max length */
							__( '- Minimum of %1$d characters, max %2$d.', 'wds' ),
							\smartcrawl_metadesc_min_length(),
							\smartcrawl_metadesc_max_length()
						)
					);
					?>
				</span>
			</label>

			<div class="sui-insert-variables wds-allow-macros">
			<textarea
				name="wds_desc"
				id="wds_metadesc"
				placeholder="<?php echo esc_attr( $desc_placeholder ); ?>"
				class="sui-form-control wds-meta-field"
			><?php echo esc_textarea( $meta_desc ); ?></textarea>
				<?php $this->render_view( 'macros-dropdown', array( 'macros' => $macros ) ); ?>
			</div>

			<p class="sui-description">
				<?php esc_html_e( 'The SEO description is used for the meta description on the archive page for this term.', 'wds' ); ?>
			</p>
		</div>
	</div>
</div>