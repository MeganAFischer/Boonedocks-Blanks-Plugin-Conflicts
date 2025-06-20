<?php
/**
 * Template: Reporting Day of Week Select.
 *
 * @package Smartcrwal
 */

$component = empty( $component ) ? '' : $component;

if ( ! $component ) {
	return;
}

$option_name = empty( $_view['option_name'] ) ? '' : $_view['option_name'];
$dow_value   = empty( $dow_value ) ? false : $dow_value;
$is_member   = ! empty( $_view['is_member'] );
$disabled    = $is_member ? '' : 'disabled';
$monday      = strtotime( 'this Monday' );
$days        = array(
	esc_html__( 'Sunday', 'wds' ),
	esc_html__( 'Monday', 'wds' ),
	esc_html__( 'Tuesday', 'wds' ),
	esc_html__( 'Wednesday', 'wds' ),
	esc_html__( 'Thursday', 'wds' ),
	esc_html__( 'Friday', 'wds' ),
	esc_html__( 'Saturday', 'wds' ),
);
$dow_range   = range( 0, 6 );

$select_id   = "wds-{$component}-dow";
$select_name = "{$option_name}[{$component}-dow]";

$timezone   = function_exists( '\wp_timezone_string' ) ? wp_timezone_string() : get_option( 'timezone_string' );
$time_label = empty( $timezone ) ? '' : sprintf( '%s (%s)', wp_date( 'h:i A' ), $timezone );
?>

<label
	for="<?php echo esc_attr( $select_id ); ?>"
	class="sui-label"
>
	<?php esc_html_e( 'Day of Week', 'wds' ); ?>
</label>

<select
	class="sui-select" <?php echo esc_attr( $disabled ); ?>
	id="<?php echo esc_attr( $select_id ); ?>"
	data-minimum-results-for-search="-1"
	name="<?php echo esc_attr( $select_name ); ?>"
>
	<?php foreach ( $dow_range as $dow ) : ?>
		<option value="<?php echo esc_attr( (string) $dow ); ?>"
			<?php selected( $dow, $dow_value ); ?>>
			<?php
			$day_number = gmdate( 'w', $monday + ( $dow * DAY_IN_SECONDS ) );
			echo esc_html( $days[ $day_number ] );
			?>
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