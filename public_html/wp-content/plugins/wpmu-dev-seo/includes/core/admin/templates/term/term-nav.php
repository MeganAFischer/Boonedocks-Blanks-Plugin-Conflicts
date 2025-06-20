<?php
/**
 * Template: Term Nav.
 *
 * @package Smartcrwal
 */

$show_onpage_tabs = ! empty( $show_onpage_tabs );
$show_social_tab  = ! empty( $show_social_tab );

$tab_items = array();
if ( $show_onpage_tabs ) {
	$tab_items['wds_seo'] = esc_html__( 'SEO', 'wds' );
}
if ( $show_social_tab ) {
	$tab_items['wds_social'] = esc_html__( 'Social', 'wds' );
}
if ( $show_onpage_tabs ) {
	$tab_items['wds_advanced'] = esc_html__( 'Advanced', 'wds' );
}
$first_tab = true;
?>
<div data-tabs>
	<?php foreach ( $tab_items as $tab_id => $tab_name ) : ?>

		<div class="<?php echo $first_tab ? 'active' : ''; ?> <?php echo esc_attr( $tab_id ); ?>-tab">
			<?php echo wp_kses_post( $tab_name ); ?>
		</div>

		<?php $first_tab = false; ?>
	<?php endforeach; ?>
</div>