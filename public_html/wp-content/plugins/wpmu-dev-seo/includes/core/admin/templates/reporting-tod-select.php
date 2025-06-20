<?php
/**
 * Template: Reporting Time of Day Select.
 *
 * @package Smartcrwal
 */

$component = empty( $component ) ? '' : $component;

if ( ! $component ) {
	return;
}
$tod_value = empty( $tod_value ) ? false : $tod_value;

$is_member   = ! empty( $_view['is_member'] );
$option_name = empty( $_view['option_name'] ) ? '' : $_view['option_name'];
$disabled    = $is_member ? '' : 'disabled';

$midnight = strtotime( 'today' );

$select_id   = "wds-{$component}-tod";
$select_name = "{$option_name}[{$component}-tod]";

$timezone   = function_exists( '\wp_timezone_string' ) ? wp_timezone_string() : get_option( 'timezone_string' );
$time_label = empty( $timezone ) ? '' : sprintf( '%s (%s)', wp_date( 'h:i A' ), $timezone );

?>

<label
	for="<?php echo esc_attr( $select_id ); ?>"
	class="sui-label"
><?php esc_html_e( 'Time of Day', 'wds' ); ?></label>

<select
	<?php echo esc_attr( $disabled ); ?>
	class="sui-select"
	id="<?php echo esc_attr( $select_id ); ?>"
	data-minimum-results-for-search="-1"
	name="<?php echo esc_attr( $select_name ); ?>"
>
	<?php foreach ( range( 0, 23 ) as $tod ) : ?>
		<option value="<?php echo esc_attr( (string) $tod ); ?>" <?php selected( $tod, $tod_value ); ?>>
			<?php echo esc_html( date_i18n( get_option( 'time_format' ), $midnight + ( $tod * HOUR_IN_SECONDS ) ) ); ?>
		</option>
	<?php endforeach; ?>
</select>

<?php if ( ! empty( $time_label ) ) : ?>
	<p class="sui-description">
		<?php
		printf(
			// translators: %1$s current time with timezone, %2$s general options page url.
			esc_html__( 'Your site\'s current time is %1$s based on your <a href="%2$s" target="_blank">WordPress Settings</a>.', 'wds' ),
			esc_html( $time_label ),
			esc_url( admin_url( 'options-general.php' ) )
		);
		?>
	</p>
<?php endif; ?>