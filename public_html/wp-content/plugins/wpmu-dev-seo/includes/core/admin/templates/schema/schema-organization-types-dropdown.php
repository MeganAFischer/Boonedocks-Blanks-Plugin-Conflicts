<?php
/**
 * Template: Schema Organization Types Dropdown.
 *
 * @package Smartcrwal
 */

namespace SmartCrawl;

use SmartCrawl\Schema\Type_Constants;

$option_name       = empty( $_view['option_name'] ) ? '' : $_view['option_name'];
$organization_type = empty( $organization_type ) ? '' : $organization_type;
?>

<div class="sui-form-field">
	<label for="organization_type" class="sui-label">
		<?php esc_html_e( 'Organization type', 'wds' ); ?>
	</label>

	<select
		id="organization_type"
		name="<?php echo esc_attr( $option_name ); ?>[organization_type]"
		data-minimum-results-for-search="-1"
		class="sui-select"
	>
		<option value=""><?php esc_html_e( 'Select (Optional)', 'wds' ); ?></option>
		<?php
		foreach (
			array(
				Type_Constants::ORGANIZATION_AIRLINE       => 'Airline',
				Type_Constants::ORGANIZATION_CONSORTIUM    => 'Consortium',
				Type_Constants::ORGANIZATION_CORPORATION   => 'Corporation',
				Type_Constants::ORGANIZATION_EDUCATIONAL   => 'Educational',
				Type_Constants::ORGANIZATION_FUNDING_SCHEME => 'Funding Scheme',
				Type_Constants::ORGANIZATION_GOVERNMENT    => 'Government',
				Type_Constants::ORGANIZATION_LIBRARY_SYSTEM => 'Library System',
				Type_Constants::ORGANIZATION_MEDICAL       => 'Medical',
				Type_Constants::ORGANIZATION_NGO           => 'NGO',
				Type_Constants::ORGANIZATION_NEWS_MEDIA    => 'News Media',
				Type_Constants::ORGANIZATION_PERFORMING_GROUP => 'Performing Group',
				Type_Constants::ORGANIZATION_PROJECT       => 'Project',
				Type_Constants::ORGANIZATION_SPORTS        => 'Sports',
				Type_Constants::ORGANIZATION_WORKERS_UNION => 'Workers Union',
			) as $org_type_value => $org_type_label
		) :
			?>
			<option
				<?php selected( $org_type_value, $organization_type ); ?>
				value="<?php echo esc_attr( $org_type_value ); ?>"
			>
				<?php echo esc_html( $org_type_label ); ?>
			</option>
		<?php endforeach; ?>
	</select>

	<p class="sui-description" style="margin-bottom: 7px;">
		<?php esc_html_e( 'Choose the type that best describes your organization website.', 'wds' ); ?>
	</p>
	<p class="sui-description">
		<?php
		echo wp_kses_post(
			\smartcrawl_format_link(
			/* translators: %s: Link to Schema types section */
				esc_html__( 'Note: If you want to add Local Business markup, you can do it by adding a “Local Business” type in the %s.', 'wds' ),
				\SmartCrawl\Admin\Settings\Admin_Settings::admin_url( Settings::TAB_SCHEMA ) . '&tab=tab_types',
				esc_html__( 'Types Builder', 'wds' )
			)
		);
		?>
	</p>
</div>