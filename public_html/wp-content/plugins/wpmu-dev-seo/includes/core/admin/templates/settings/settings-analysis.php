<?php
/**
 * Template: Settings Analysis.
 *
 * @package Smartcrwal
 */

namespace SmartCrawl;

use SmartCrawl\Controllers\Analysis_Content;
use SmartCrawl\Readability\Controller;

$option_name          = empty( $_view['option_name'] ) ? '' : $_view['option_name'];
$strong               = '<strong>%s</strong>';
$analysis_strategy    = Analysis_Content::get()->get_analysis_strategy();
$is_strategy_strict   = Analysis_Content::STRATEGY_STRICT === $analysis_strategy;
$is_strategy_moderate = Analysis_Content::STRATEGY_MODERATE === $analysis_strategy;
$is_strategy_manual   = Analysis_Content::STRATEGY_MANUAL === $analysis_strategy;
$is_strategy_loose    = Analysis_Content::STRATEGY_LOOSE === $analysis_strategy;
// Check if current language is supported for readability analysis.
$lang_supported = Controller::get()->is_language_supported();

$is_disalbed = empty( $_view['options']['analysis-seo'] ) && empty( $_view['options']['analysis-readability'] );
?>

<div class="sui-box-settings-row wds-in-post-analysis"<?php echo $is_disalbed ? ' style="display: none;"' : ''; ?>>
	<div class="sui-box-settings-col-1">
		<label class="sui-settings-label"><?php esc_html_e( 'In-Post Analysis', 'wds' ); ?></label>
		<p class="sui-description">
			<?php esc_html_e( 'These modules appear inside the WordPress Post Editor and provide per-page SEO and Readability analysis to fine tune each post to focus keyphrases.', 'wds' ); ?>
		</p>
	</div>

	<div class="sui-box-settings-col-2">
		<?php
		if ( ! $lang_supported ) {
			$this->render_view(
				'notice',
				array(
					'class'   => 'sui-notice-yellow',
					'message' => sprintf(
					// translators: %s link to documentation.
						__( 'This feature may not work as expected as our SEO analysis engine doesn\'t support your current site language. For better results, change the language in WordPress settings to one of the <a href="%s" target="_blank">supported languages</a>.', 'wds' ),
						'https://wpmudev.com/docs/wpmu-dev-plugins/smartcrawl/#in-post-analysis'
					),
				)
			);
		}
		?>

		<label class="sui-settings-label"><?php esc_html_e( 'Engine', 'wds' ); ?></label>
		<p class="sui-description">
			<?php
			printf(
				/* translators: 1,2: strong tag, 3: plugin title */
				esc_html__( 'Choose how you want %1$s%3$s%2$s to analyze your content.', 'wds' ),
				'<strong>',
				'</strong>',
				esc_html( \smartcrawl_get_plugin_title() )
			);
			?>
		</p>
		<p class="sui-description">
			<?php esc_html__( '<strong>Content</strong> is recommended for most websites as it only reviews the_content() output.', 'wds' ); ?>
		</p>
		<p class="sui-description">
			<?php esc_html__( '<strong>Wide</strong> includes everything, except for your header, nav, footer and sidebars. This can be helpful for page builders and themes with custom output.', 'wds' ); ?>
		</p>
		<p class="sui-description">
			<?php esc_html__( '<strong>All</strong> checks your entire page’s content including elements like nav and footer. Due to analysing everything you might miss key analysis of your real content so we don’t recommend this approach.', 'wds' ); ?>
		</p>
		<p class="sui-description">
			<?php esc_html__( '<strong>None</strong> only analyzes content you tell it programmatically. If you have a fully custom setup, this is the option for you. Read the documentation.', 'wds' ); ?>
		</p>

		<div class="wds-analysis-strategy-tabs sui-side-tabs sui-tabs">
			<div class="sui-tabs-menu">
				<label class="wds-strategy-strict sui-tab-item <?php echo $is_strategy_strict ? 'active' : ''; ?>">
					<?php esc_html_e( 'Content', 'wds' ); ?>
					<input
						name="<?php echo esc_attr( $option_name ); ?>[analysis_strategy]"
						value="<?php echo esc_attr( Analysis_Content::STRATEGY_STRICT ); ?>"
						type="radio" <?php checked( $is_strategy_strict ); ?>
						class="hidden"
					/>
				</label>
				<label class="wds-strategy-moderate sui-tab-item <?php echo $is_strategy_moderate ? 'active' : ''; ?>">
					<?php esc_html_e( 'Wide', 'wds' ); ?>
					<input
						name="<?php echo esc_attr( $option_name ); ?>[analysis_strategy]"
						value="<?php echo esc_attr( Analysis_Content::STRATEGY_MODERATE ); ?>"
						type="radio" <?php checked( $is_strategy_moderate ); ?>
						class="hidden"
					/>
				</label>
				<label class="wds-strategy-loose sui-tab-item <?php echo $is_strategy_loose ? 'active' : ''; ?>">
					<?php esc_html_e( 'All', 'wds' ); ?>
					<input
						name="<?php echo esc_attr( $option_name ); ?>[analysis_strategy]"
						value="<?php echo esc_attr( Analysis_Content::STRATEGY_LOOSE ); ?>"
						type="radio" <?php checked( $is_strategy_loose ); ?>
						class="hidden"
					/>
				</label>
				<label class="wds-strategy-manual sui-tab-item <?php echo $is_strategy_manual ? 'active' : ''; ?>">
					<?php esc_html_e( 'None', 'wds' ); ?>
					<input
						name="<?php echo esc_attr( $option_name ); ?>[analysis_strategy]"
						value="<?php echo esc_attr( Analysis_Content::STRATEGY_MANUAL ); ?>"
						type="radio" <?php checked( $is_strategy_manual ); ?>
						class="hidden"
					/>
				</label>
			</div>
		</div>

		<?php
		$this->render_view(
			'notice',
			array(
				'message' => sprintf(
				/* translators: 1: "None" within <strong> tag, 2: Class selector */
					esc_html__( 'Custom setup? Choose the %1$s method and add the class %2$s to container elements you want to include in the SEO and Readability Analysis.', 'wds' ),
					'<strong>' . esc_html__( 'None', 'wds' ) . '</strong>',
					'<strong>' . esc_html( '.smartcrawl-checkup-included' ) . '</strong>'
				),
				'class'   => 'grey',
			)
		);
		?>

		<?php
		$this->render_view(
			'toggle-item',
			array(
				'item_value'       => 'disable-analysis-on-list',
				'field_name'       => "{$option_name}[disable-analysis-on-list]",
				'field_id'         => 'disable-analysis-on-list',
				'checked'          => ! empty( $_view['options']['disable-analysis-on-list'] ),
				'item_label'       => __( 'Disable Page Analysis Check on Pages/Posts Screen', 'wds' ),
				'item_description' => __( 'By default, posts and pages are analyzed one at a time to avoid excessive server load. You can use this option to disable these checks on the pages and posts screens.', 'wds' ),
			)
		);
		?>
	</div>
</div>