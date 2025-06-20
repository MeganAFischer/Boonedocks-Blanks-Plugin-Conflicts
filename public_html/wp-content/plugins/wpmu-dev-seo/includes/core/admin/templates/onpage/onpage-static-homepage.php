<?php
/**
 * Template: Onpage Static Homepage.
 *
 * @package Smartcrwal
 */

namespace SmartCrawl;

use SmartCrawl\Admin\Settings\Onpage;

$front_page        = empty( $front_page ) ? null : $front_page;
$front_page_notice = empty( $front_page_notice ) ? '' : $front_page_notice;
if ( ! is_a( $front_page, '\WP_Post' ) ) {
	return;
}

$smartcrawl_post = \SmartCrawl\Cache\Post_Cache::get()->get_post( $front_page->ID );
if ( ! $smartcrawl_post ) {
	return;
}
$meta_title_placeholder = $smartcrawl_post->get_meta_title();
$meta_desc_placeholder  = $smartcrawl_post->get_meta_description();
$macros                 = array_merge(
	Onpage::get_singular_macros( 'page' ),
	Onpage::get_general_macros()
);
?>

<?php echo wp_kses_post( $front_page_notice ); ?>

<?php $this->render_view( 'onpage/onpage-preview' ); ?>

<?php
$this->render_view(
	'onpage/onpage-general-settings-inner',
	array(
		'title'                   => \smartcrawl_get_value( 'title', $front_page->ID ),
		'title_placeholder'       => $meta_title_placeholder,
		'title_key'               => 'title-static-home',
		'title_label_desc'        => esc_html__( 'Define the main title of your website that Google will index.', 'wds' ),
		'title_field_desc'        => esc_html__( 'This is generally your brand name, sometimes with a tagline.', 'wds' ),
		'description'             => \smartcrawl_get_value( 'metadesc', $front_page->ID ),
		'description_placeholder' => $meta_desc_placeholder,
		'description_key'         => 'metadesc-static-home',
		'meta_label_desc'         => esc_html__( 'Set the default description that will accompany your SEO title in search engine results.', 'wds' ),
		'meta_field_desc'         => esc_html__( 'Remember to keep it simple, to the point, and include a bit about what your website can offer potential visitors.', 'wds' ),
		'macros'                  => $macros,
	)
);

$this->render_view(
	'metabox/metabox-social-opengraph',
	array(
		'post' => $front_page,
	)
);

$this->render_view(
	'metabox/metabox-social-twitter',
	array(
		'post' => $front_page,
	)
);

$this->render_view(
	'metabox/metabox-advanced-indexing',
	array(
		'robots_noindex_value'  => (int) \smartcrawl_get_value( 'meta-robots-noindex', $front_page->ID ),
		'robots_nofollow_value' => (int) \smartcrawl_get_value( 'meta-robots-nofollow', $front_page->ID ),
		'robots_index_value'    => (int) \smartcrawl_get_value( 'meta-robots-index', $front_page->ID ),
		'robots_follow_value'   => (int) \smartcrawl_get_value( 'meta-robots-follow', $front_page->ID ),
		'advanced_value'        => explode( ',', \smartcrawl_get_value( 'meta-robots-adv', $front_page->ID ) ),
		'post_type_noindexed'   => (bool) \smartcrawl_get_array_value( $_view['options'], 'meta_robots-noindex-page' ),
		'post_type_nofollowed'  => (bool) \smartcrawl_get_array_value( $_view['options'], 'meta_robots-nofollow-page' ),
	)
);
?>

<footer class="sui-box-footer">
	<button
		type="button"
		class="sui-button sui-button-blue wds-save-static-home-settings">
		<span class="sui-loading-text">
			<span class="sui-icon-save" aria-hidden="true"></span>
			<?php echo esc_html__( 'Save Settings', 'wds' ); ?>
		</span>
		<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
	</button>
</footer>