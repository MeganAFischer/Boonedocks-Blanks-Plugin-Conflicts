<?php
/**
 * Template: Dashboard Schema Widget.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl;

use SmartCrawl\Schema\Types;
use SmartCrawl\Admin\Settings\Admin_Settings;

if ( ! Admin_Settings::is_tab_allowed( Settings::TAB_SCHEMA ) ) {
	return;
}

$page_url           = Admin_Settings::admin_url( Settings::TAB_SCHEMA );
$social_option_name = Settings::TAB_SOCIAL . '_options';
$social_options     = Settings::get_specific_options( $social_option_name );
$schema_enabled     = ! \smartcrawl_get_array_value( $social_options, 'disable-schema' );
$schema_types       = Types::get()->get_schema_types();
$option_name        = Settings::SETTINGS_MODULE . '_options';

$settings_opts = Settings::get_specific_options( $option_name );
$hide_disables = \smartcrawl_get_array_value( $settings_opts, 'hide_disables', true );

if ( ! $schema_enabled && $hide_disables ) {
	return '';
}
?>

<section
	id="<?php echo esc_attr( \SmartCrawl\Admin\Settings\Dashboard::BOX_SCHEMA ); ?>"
	class="sui-box wds-dashboard-widget">

	<div class="sui-box-header">
		<h2 class="sui-box-title">
			<span class="sui-icon-code" aria-hidden="true"></span> <?php esc_html_e( 'Schema', 'wds' ); ?>
		</h2>
	</div>

	<div class="sui-box-body">
		<p><?php esc_html_e( 'Quickly add Schema to your pages to help Search Engines understand and show your content better.', 'wds' ); ?></p>

		<?php if ( $schema_enabled ) : ?>
			<div class="wds-default-schema wds-separator-top wds-draw-left-padded">
				<small><strong><?php esc_html_e( 'Default Markup', 'wds' ); ?></strong></small>
				<span
					class="sui-tooltip sui-tooltip-constrained"
					<?php /* translators: 1: plugin title */ ?>
					data-tooltip="<?php printf( esc_html__( '%s automatically computes a schema structure for your pages based on your schema settings. By default, Article type is printed for all post types. You can replace this automatically-generated schema type by configuring types in the schema type builder.', 'wds' ), esc_html( \smartcrawl_get_plugin_title() ) ); ?>">
					<span class="sui-notice-icon sui-icon-info sui-sm" aria-hidden="true"></span>
				</span>
				<span class="sui-tag wds-right sui-tag-sm sui-tag-blue">
					<?php echo esc_html__( 'Active', 'wds' ); ?>
				</span>
			</div>

			<?php foreach ( $schema_types as $schema_type ) : ?>
				<?php
				$schema_type_label    = \smartcrawl_get_array_value( $schema_type, 'label' );
				$schema_type_disabled = \smartcrawl_get_array_value( $schema_type, 'disabled' );
				?>

				<div class="wds-separator-top wds-draw-left-padded">
					<small><strong><?php echo esc_html( $schema_type_label ); ?></strong></small>
					<span class="sui-tag wds-right sui-tag-sm <?php echo $schema_type_disabled ? 'sui-tag-disabled' : 'sui-tag-blue'; ?>">
						<?php
						echo $schema_type_disabled
							? esc_html__( 'Inactive', 'wds' )
							: esc_html__( 'Active', 'wds' );
						?>
					</span>
				</div>
			<?php endforeach; ?>

			<div
				class="wds-separator-top wds-draw-left-padded"
				style="display: flex; justify-content: space-between;">
				<a
					href="<?php echo esc_attr( $page_url ); ?>"
					aria-label="<?php esc_html_e( 'Configure schema component', 'wds' ); ?>"
					class="sui-button sui-button-ghost">
					<span
						class="sui-icon-wrench-tool"
						aria-hidden="true"></span> <?php esc_html_e( 'Configure', 'wds' ); ?>
				</a>

				<a
					href="<?php echo esc_attr( $page_url . '&tab=tab_types&add_type=1' ); ?>"
					aria-label="<?php esc_html_e( 'Add new schema type', 'wds' ); ?>"
					class="sui-button sui-button-blue">
					<span class="sui-icon-plus" aria-hidden="true"></span>
					<?php echo esc_html__( 'Add Type', 'wds' ); ?>
				</a>
			</div>
		<?php else : ?>
			<br/>
			<button
				type="button"
				data-option-id="<?php echo esc_attr( $social_option_name ); ?>"
				data-flag="disable-schema"
				data-value="0"
				aria-label="<?php esc_html_e( 'Activate schema component', 'wds' ); ?>"
				class="wds-activate-component wds-disabled-during-request sui-button sui-button-blue">

				<span class="sui-loading-text"><?php esc_html_e( 'Activate', 'wds' ); ?></span>
				<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
			</button>
		<?php endif; ?>
	</div>
</section>