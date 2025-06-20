<?php
/**
 * Template: Reporting Date of Month Select.
 *
 * @package Smartcrwal
 */

$component = empty( $component ) ? '' : $component;

if ( ! $component ) {
	return;
}

$option_name = empty( $_view['option_name'] ) ? '' : $_view['option_name'];
$dom_value   = empty( $dom_value ) ? false : $dom_value;
$is_member   = ! empty( $_view['is_member'] );
$disabled    = $is_member ? '' : 'disabled';
$dom_range   = range( 1, 28 );

$select_id   = "wds-{$component}-dom";
$select_name = "{$option_name}[{$component}-dom]";

$timezone   = function_exists( '\wp_timezone_string' ) ? wp_timezone_string() : get_option( 'timezone_string' );
$time_label = empty( $timezone ) ? '' : sprintf( '%s (%s)', wp_date( 'h:i A' ), $timezone );
?>

<label
	for="<?php echo esc_attr( $select_id ); ?>"
	class="sui-label"
>
	<?php esc_html_e( 'Date of Month', 'wds' ); ?>
</label>

<select
	class="sui-select" <?php echo esc_attr( $disabled ); ?>
	id="<?php echo esc_attr( $select_id ); ?>"
	data-minimum-results-for-search="-1"
	name="<?php echo esc_attr( $select_name ); ?>"
>
	<?php foreach ( $dom_range as $dom ) : ?>
		<option value="<?php echo esc_attr( (string) $dom ); ?>"
			<?php selected( $dom, $dom_value ); ?>>
			<?php echo esc_html( (string) $dom ); ?>
		</option>
	<?php endforeach; ?>
</select>

<?php if ( ! empty( $time_label ) ) : ?>
	<p class="sui-description">
		<?php
		printf(
		// translators: 1: current time with timezone, 2, 3: opening/closing anchor tags.
			esc_html__( 'Your site\'s current time is %1$s based on your %2$sWordPress Settings%3$s.', 'wds' ),
			esc_html( $time_label ),
			'<a href="' . esc_url( admin_url( 'options-general.php' ) ) . '" target="_blank">',
			'</a>'
		);
		?>
	</p>
<?php endif; ?>