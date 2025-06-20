<?php
/**
 * Template: Vertical Tabs SideNav.
 *
 * @package Smartcrwal
 */

namespace SmartCrawl;

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
$tabs       = empty( $tabs ) || ! is_array( $tabs ) ? array() : $tabs;
$active_tab = empty( $active_tab ) ? '' : $active_tab;
?>
<div class="wds-vertical-tabs sui-sidenav">
	<ul class="sui-vertical-tabs">
		<?php foreach ( $tabs as $tab ) : ?>
			<?php
			$tab_id     = \smartcrawl_get_array_value( $tab, 'id' );
			$tab_name   = apply_filters( 'smartcrawl_vertical_side_nav_name', \smartcrawl_get_array_value( $tab, 'name' ), $tab_id );
			$tab_name   = wp_kses( $tab_name, array( 'span' => array( 'class' => array() ) ) );
			$spinner    = \smartcrawl_get_array_value( $tab, 'spinner' );
			$tag_value  = \smartcrawl_get_array_value( $tab, 'tag_value' );
			$tag_class  = \smartcrawl_get_array_value( $tab, 'tag_class' );
			$tick       = \smartcrawl_get_array_value( $tab, 'tick' );
			$tick_class = \smartcrawl_get_array_value( $tab, 'tick_class' );
			$tick_class = empty( $tick_class ) ? 'sui-success' : $tick_class;
			?>
			<li class="sui-vertical-tab <?php echo esc_attr( $tab_id ); ?> <?php echo $active_tab === $tab_id ? esc_attr( 'current' ) : ''; ?>">

				<a
					role="button"
					data-target="<?php echo esc_attr( $tab_id ); ?>"
					href="#"
					<?php /* translators: %s: Tab name */ ?>
					aria-label="<?php printf( esc_html__( '%s tab', 'wds' ), esc_html( wp_strip_all_tags( $tab_name ) ) ); ?>"
				>
					<?php echo wp_kses_post( $tab_name ); ?>
				</a>

				<span
					class="sui-icon-loader sui-loading"
					aria-hidden="true"
					style="<?php echo $spinner ? '' : 'display:none;'; ?>"
				></span>

				<span
					class="sui-tag <?php echo esc_attr( $tag_class ); ?>"
					style="<?php echo $tag_value ? '' : 'display:none;'; ?>"
				>
					<?php echo esc_html( $tag_value ); ?>
				</span>

				<span
					class="sui-icon-check-tick <?php echo esc_attr( $tick_class ); ?>"
					aria-hidden="true"
					style="<?php echo $tick ? '' : 'display:none;'; ?>"
				></span>
			</li>
		<?php endforeach; ?>
	</ul>
</div>