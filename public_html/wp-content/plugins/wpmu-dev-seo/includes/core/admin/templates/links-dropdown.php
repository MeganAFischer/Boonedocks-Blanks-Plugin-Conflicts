<?php
/**
 * Template: Displaying a links' dropdown.
 *
 * @package smartcrawl
 */

$label = empty( $label ) ? '' : $label;
$links = empty( $links ) ? array() : $links;
?>

<div class="sui-dropdown wds-links-dropdown">
	<button class="sui-button-icon sui-dropdown-anchor" aria-label="Dropdown">
		<span class="sui-loading-text">
			<span class="sui-icon-widget-settings-config" aria-hidden="true"></span>
		</span>

		<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
	</button>
	<ul>
		<?php foreach ( $links as $href => $text ) : ?>
			<li><a href="<?php echo esc_attr( $href ); ?>"><?php echo esc_html( $text ); ?></a></li>
		<?php endforeach; ?>
	</ul>
</div>