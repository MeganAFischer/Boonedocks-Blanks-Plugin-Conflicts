<?php
/**
 * Settings general section template
 *
 * @package SmartCrawl
 */

namespace SmartCrawl;

use SmartCrawl\Admin\Settings\Dashboard;

$option_name    = empty( $_view['option_name'] ) ? '' : $_view['option_name'];
$plugin_modules = empty( $plugin_modules ) ? array() : $plugin_modules;
?>

<div id="wds-conflicting-plugins"></div>

<div class="sui-box-settings-row">
	<div class="sui-box-settings-col-1">
		<label class="sui-settings-label"><?php esc_html_e( 'Plugin Modules', 'wds' ); ?></label>
		<p class="sui-description">
			<?php esc_html_e( 'Choose the modules you would like to activate.', 'wds' ); ?>
		</p>
	</div>

	<div class="sui-box-settings-col-2">
		<?php
		$this->render_view(
			'toggle-item',
			array(
				'item_value' => 'analysis-seo',
				'field_name' => "{$option_name}[analysis-seo]",
				'field_id'   => 'analysis-seo',
				'checked'    => ! empty( $_view['options']['analysis-seo'] ),
				'html_label' => '<span class="sui-tooltip sui-tooltip-constrained" data-tooltip="' . esc_attr__( 'SEO Analysis benchmarks your content against recommended SEO practices and gives suggestions for improvement to ensure content is as optimized as possible.', 'wds' ) . '" style="--tooltip-width: 240px;">' . esc_html__( 'SEO Analysis', 'wds' ) . '</span>',
			)
		);
		$this->render_view(
			'toggle-item',
			array(
				'item_value' => 'analysis-readability',
				'field_name' => "{$option_name}[analysis-readability]",
				'field_id'   => 'analysis-readability',
				'checked'    => ! empty( $_view['options']['analysis-readability'] ),
				'html_label' => '<span class="sui-tooltip sui-tooltip-constrained" data-tooltip="' . esc_attr__( 'Readability Analysis uses the Flesch-Kincaid test to determine how easy your content is to read.', 'wds' ) . '" style="--tooltip-width: 240px;">' . esc_html__( 'Readability Analysis', 'wds' ) . '</span>',
			)
		);

		foreach ( $plugin_modules as $plugin_module ) {
			$this->render_view( 'toggle-item', $plugin_module );
		}
		?>

		<div id="wds-plugin-modules"></div>
	</div>
</div>

<?php
$sitemap_option_name = empty( $sitemap_option_name ) ? '' : $sitemap_option_name;
$verification_pages  = empty( $verification_pages ) ? array() : $verification_pages;
$smartcrawl_options  = Settings::get_options();
$sitemap_options     = Settings::get_component_options( Settings::COMP_SITEMAP );
$usage_tracking      = (bool) \smartcrawl_get_array_value( $smartcrawl_options, 'usage_tracking' );

$this->render_view( 'settings/settings-analysis' );

$this->render_view(
	'toggle-group',
	array(
		'label'       => esc_html__( 'Admin Bar', 'wds' ),
		'description' => sprintf(
			/* translators: 1,2: strong tag, 3: plugin title */
			esc_html__( 'Add a shortcut to %1$s%3$s%2$s settings in the top WordPress Admin bar.', 'wds' ),
			'<strong>',
			'</strong>',
			\smartcrawl_get_plugin_title()
		),
		'separator'   => true,
		'items'       => array(
			'extras-admin_bar' => array(
				/* translators: %s: plugin title */
				'label' => sprintf( esc_html__( 'Enable %s shortcut', 'wds' ), \smartcrawl_get_plugin_title() ),
			),
		),
	)
);
?>

<?php
$this->render_view(
	'toggle-group',
	array(
		'label'       => __( 'Meta Tags', 'wds' ),
		'description' => sprintf(
			/* translators: 1,2: strong tag, 3: plugin title */
			__( 'Choose what %1$s%3$s%2$s modules you want available to use.', 'wds' ),
			'<strong>',
			'</strong>',
			\smartcrawl_get_plugin_title()
		),
		'separator'   => true,
		'items'       => array(
			'general-suppress-generator'           => array(
				'label'       => __( 'Hide generator meta tag', 'wds' ),
				'description' => __( 'It can be considered a security risk to have your WordPress version visible to the public, so we recommend you hide it.', 'wds' ),
			),
			'general-suppress-redundant_canonical' => array(
				'label'       => __( 'Hide redundant canonical link tags', 'wds' ),
				'description' => __( 'WordPress automatically generates a canonical tag for your website, but in many cases this isn’t needed so you can turn it off to avoid any potential SEO ‘duplicate content’ backlash from search engines.', 'wds' ),
			),
			'metabox-lax_enforcement'              => array(
				'label'       => __( 'Enforce meta tag character limits', 'wds' ),
				'description' => __( 'Each meta tag type has recommended maximum characters lengths to follow. Turning this off will remove the enforcement preventing you from adding too many characters.', 'wds' ),
			),
		),
	)
);
?>

<div class="sui-box-settings-row wds-verification-tags">
	<div class="sui-box-settings-col-1">
		<label class="sui-settings-label"><?php esc_html_e( 'Search engines', 'wds' ); ?></label>
		<p class="sui-description"><?php esc_html_e( 'This tool will add the meta tags required by search engines to verify your site with their SEO management tools to your websites <head> tag.', 'wds' ); ?></p>
	</div>
	<div class="sui-box-settings-col-2">
		<div class="sui-form-field">
			<?php
			$value = isset( $sitemap_options['verification-google-meta'] ) ? $sitemap_options['verification-google-meta'] : '';
			?>
			<label
				for="verification-google"
				class="sui-settings-label"
			><?php esc_html_e( 'Google Verification', 'wds' ); ?></label>
			<div class="sui-description">
				<?php esc_html_e( 'Paste the full meta tag from Google.', 'wds' ); ?>
			</div>
			<input
				id='verification-google'
				name='<?php echo esc_attr( $_view['option_name'] ); ?>[verification-google-meta]'
				type='text'
				placeholder="<?php echo esc_attr( 'E.g. <meta name="google-site-verification" content="...' ); ?>"
				class='sui-form-control'
				value='<?php echo esc_attr( $value ); ?>'
			>
		</div>

		<div class="sui-form-field">
			<?php
			$value = isset( $sitemap_options['verification-bing-meta'] ) ? $sitemap_options['verification-bing-meta'] : '';
			?>
			<label
				for="verification-bing"
				class="sui-settings-label"
			><?php esc_html_e( 'Bing Verification', 'wds' ); ?></label>
			<div class="sui-description">
				<?php esc_html_e( 'Paste the full meta tag from Bing.', 'wds' ); ?>
			</div>
			<input
				id='verification-bing'
				name='<?php echo esc_attr( $_view['option_name'] ); ?>[verification-bing-meta]'
				type='text'
				class='sui-form-control'
				placeholder="<?php echo esc_attr( 'E.g. <meta name="msvalidate.01" content="...' ); ?>"
				value='<?php echo esc_attr( $value ); ?>'
			>
		</div>

		<div class="sui-form-field">
			<label
				for="verification-pages"
				class="sui-settings-label"
			><?php esc_html_e( 'Add verification code to', 'wds' ); ?></label>
			<select
				id="verification-pages"
				data-minimum-results-for-search="-1"
				name="<?php echo esc_attr( $_view['option_name'] ); ?>[verification-pages]"
				class="sui-select"
			>
				<?php foreach ( $verification_pages as $item => $label ) : ?>
					<?php
					$selected = isset( $sitemap_options['verification-pages'] ) && $sitemap_options['verification-pages'] === $item ? 'selected' : '';
					?>
					<option
						value="<?php echo esc_attr( $item ); ?>"
						<?php echo esc_attr( $selected ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="sui-form-field">
			<div class="wds-custom-meta-tags">
				<label
					for="verification-google"
					class="sui-settings-label"
				><?php esc_html_e( 'Custom meta tags', 'wds' ); ?></label>
				<span class="sui-description"><?php esc_html_e( 'Have more meta tags you want to add? Add as many as you like.', 'wds' ); ?></span>

				<?php if ( ! empty( $sitemap_options['additional-metas'] ) && is_array( $sitemap_options['additional-metas'] ) ) : ?>
					<?php
					foreach ( $sitemap_options['additional-metas'] as $custom_value ) {
						$this->render_view(
							'settings/settings-custom-meta-tag',
							array(
								'value' => $custom_value,
							)
						);
					}
					?>
				<?php endif; ?>

				<?php $this->render_view( 'settings/settings-custom-meta-tag' ); ?>

				<button
					type="button"
					class="sui-button sui-button-ghost"
				>
					<span class="sui-icon-plus" aria-hidden="true"></span>

					<?php esc_html_e( 'Add Another', 'wds' ); ?>
				</button>
			</div>
		</div>
	</div>
</div>

<?php if ( \smartcrawl_is_tracking_allowed() ) : ?>

	<div class="sui-box-settings-row">
		<div class="sui-box-settings-col-1">
			<label class="sui-settings-label"><?php esc_html_e( 'Usage Tracking', 'wds' ); ?></label>
			<p class="sui-description">
				<?php
				$show_doc_link = \smartcrawl_is_doc_link_enabled();

				if ( $show_doc_link ) :
					printf(
					/* translators: 1, 2: opening/closing anchor tag, 3,4: strong tag, 5: plugin title */
						esc_html__( 'Help us improve %3$s%5$s%4$s by sharing anonymous, and non-sensitive usage data. See %1$smore info%2$s about the data we collect.', 'wds' ),
						'<a href="https://wpmudev.com/docs/privacy/our-plugins/#usage-tracking" target="_blank">',
						'</a>',
						'<strong>',
						'</strong>',
						esc_html( \smartcrawl_get_plugin_title() )
					);
				else :
					printf(
						/* translators: 1,2: strong tag, 3: plugin title */
						esc_html__( 'Help us improve %1$s%3$s%2$s, by sharing anonymous, and non-sensitive usage data.', 'wds' ),
						'<strong>',
						'</strong>',
						esc_html( \smartcrawl_get_plugin_title() )
					);
				endif;
				?>
			</p>
		</div>
		<div class="sui-box-settings-col-2">
			<div class="sui-form-field">
				<label for="usage-tracking" class="sui-toggle">
					<input
						type="checkbox"
						id="usage-tracking"
						name="<?php echo esc_attr( $option_name ); ?>[usage_tracking]"
						aria-labelledby="usage-tracking-label"
						aria-describedby="usage-tracking-description"
						<?php checked( $usage_tracking ); ?>
					/>
					<span class="sui-toggle-slider" aria-hidden="true"></span>
					<span id="usage-tracking-label" class="sui-toggle-label"><?php esc_html_e( 'Allow Usage Tracking', 'wds' ); ?></span>
					<span id="usage-tracking-description" class="sui-description"><?php esc_html_e( 'Note: Usage tracking is completely anonymous and non-sensitive, and we only track features you are/aren’t using to make more informed feature decisions.', 'wds' ); ?></span>
				</label>
			</div>
		</div>
	</div>

	<?php
endif;