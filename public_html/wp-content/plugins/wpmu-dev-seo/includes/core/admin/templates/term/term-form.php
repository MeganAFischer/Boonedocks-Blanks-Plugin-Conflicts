<?php
/**
 * Template: Term Form.
 *
 * @package Smartcrwal
 */

namespace SmartCrawl;

use SmartCrawl\Admin\Settings\Admin_Settings;

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
$tax_meta        = empty( $tax_meta ) ? array() : $tax_meta;
$term            = empty( $term ) ? null : $term;
$global_noindex  = empty( $global_noindex ) ? false : $global_noindex;
$global_nofollow = empty( $global_nofollow ) ? false : $global_nofollow;
$title_key       = empty( $title_key ) ? '' : $title_key;
$desc_key        = empty( $desc_key ) ? '' : $desc_key;

$all_options              = Settings::get_options();
$og_setting_enabled       = (bool) \smartcrawl_get_array_value( $all_options, 'og-enable' );
$og_taxonomy_enabled      = (bool) \smartcrawl_get_array_value( $all_options, 'og-active-' . $term->taxonomy );
$twitter_setting_enabled  = (bool) \smartcrawl_get_array_value( $all_options, 'twitter-card-enable' );
$twitter_taxonomy_enabled = (bool) \smartcrawl_get_array_value( $all_options, 'twitter-active-' . $term->taxonomy );
$show_social_tab          = ( $og_setting_enabled && $og_taxonomy_enabled ) || ( $twitter_setting_enabled && $twitter_taxonomy_enabled );
$show_social_tab          = $show_social_tab && Settings::get_setting( 'social' ) && Admin_Settings::is_tab_allowed( Settings::TAB_SOCIAL );
$show_onpage_tabs         = Settings::get_setting( 'onpage' ) && Admin_Settings::is_tab_allowed( Settings::TAB_ONPAGE );
if ( ! $show_social_tab && ! $show_onpage_tabs ) {
	return;
}
?>

<div class="<?php echo esc_attr( \smartcrawl_sui_class() ); ?>">
	<div class="<?php \smartcrawl_wrap_class( 'wds-metabox' ); ?>">

		<div class="sui-box">
			<div class="sui-box-header">
				<h2 class="sui-box-title"><?php echo esc_html( \smartcrawl_get_plugin_title() ); ?></h2>
			</div>

			<div>

				<div class="sui-tabs">

					<?php
					$this->render_view(
						'term/term-nav',
						array(
							'show_onpage_tabs' => $show_onpage_tabs,
							'show_social_tab'  => $show_social_tab,
						)
					);
					$is_active = true;
					?>

					<div data-panes>

						<?php
						if ( $show_onpage_tabs ) {
							$this->render_view(
								'term/term-seo-tab',
								array(
									'is_active' => $is_active,
									'tax_meta'  => $tax_meta,
									'term'      => $term,
									'title_key' => $title_key,
									'desc_key'  => $desc_key,
								)
							);
							$is_active = false;
						}

						if ( $show_social_tab ) {
							// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned, WordPress.Arrays.MultipleStatementAlignment.LongIndexSpaceBeforeDoubleArrow
							$this->render_view(
								'term/term-social-tab',
								array(
									'is_active'                => $is_active,
									'tax_meta'                 => $tax_meta,
									'term'                     => $term,
									'og_setting_enabled'       => $og_setting_enabled,
									'og_taxonomy_enabled'      => $og_taxonomy_enabled,
									'twitter_setting_enabled'  => $twitter_setting_enabled,
									'twitter_taxonomy_enabled' => $twitter_taxonomy_enabled,
								)
							);
							// phpcs:enable
							$is_active = false;
						}

						if ( $show_onpage_tabs ) {
							$this->render_view(
								'term/term-advanced-tab',
								array(
									'is_active'       => $is_active,
									'tax_meta'        => $tax_meta,
									'term'            => $term,
									'global_nofollow' => $global_nofollow,
									'global_noindex'  => $global_noindex,
								)
							);
							$is_active = false;
						}
						?>
					</div>
				</div>

			</div>
		</div>

	</div>
</div>