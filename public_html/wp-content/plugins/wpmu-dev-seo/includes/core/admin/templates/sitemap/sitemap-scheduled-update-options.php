<?php
/**
 * Template for scheduled sitemap updates.
 *
 * @package SmartCrawl
 */

?>
<p class="sui-description">
	<?php esc_html_e( 'Select how often the sitemap should be updated.', 'wds' ); ?>
</p>

<?php $this->render_view(
	'reporting-schedule',
	array(
		'component'            => 'sitemap-update',
		'excluded_frequencies' => array( 'monthly' ),
		'frequency'            => empty( $_view['options']['sitemap-update-frequency'] ) ? 'daily' : $_view['options']['sitemap-update-frequency'],
		'dow_value'            => isset( $_view['options']['sitemap-update-dow'] ) ? $_view['options']['sitemap-update-dow'] : false,
		'tod_value'            => isset( $_view['options']['sitemap-update-tod'] ) ? $_view['options']['sitemap-update-tod'] : false,
	)
); ?>