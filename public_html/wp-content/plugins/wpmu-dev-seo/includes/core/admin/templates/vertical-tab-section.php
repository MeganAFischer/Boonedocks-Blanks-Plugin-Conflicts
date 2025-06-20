<?php
/**
 * Template: Vertical Tab section.
 *
 * @package Smartcrwal
 */

$section_description        = empty( $section_description ) ? '' : $section_description;
$section_template           = empty( $section_template ) ? '' : $section_template;
$section_args               = empty( $section_args ) ? array() : $section_args;
$section_type               = empty( $section_type ) ? '' : $section_type;
$description_extra_template = empty( $description_extra_template ) ? '' : $description_extra_template;
$description_extra_args     = empty( $description_extra_args ) ? array() : $description_extra_args;
?>

<div data-type="<?php echo esc_attr( $section_type ); ?>">
	<?php if ( $section_description ) : ?>
		<p><?php echo esc_html( $section_description ); ?></p>
	<?php endif; ?>
	<?php if ( ! empty( $description_extra_template ) ) : ?>
		<?php $this->render_view( $description_extra_template, $description_extra_args ); ?>
	<?php endif; ?>

	<?php $this->render_view( $section_template, $section_args ); ?>
</div>