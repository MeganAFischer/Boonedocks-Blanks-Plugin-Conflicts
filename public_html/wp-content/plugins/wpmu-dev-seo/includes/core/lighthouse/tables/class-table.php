<?php
/**
 * Table class for rendering Lighthouse reports.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Lighthouse\Tables;

/**
 * Table class.
 *
 * Handles the rendering of Lighthouse report tables.
 */
class Table {
	/**
	 * Header of the table.
	 *
	 * @var array
	 */
	private $header;
	/**
	 * Report data for the table.
	 *
	 * @var object
	 */
	private $report;
	/**
	 * Rows of the table.
	 *
	 * @var array
	 */
	private $rows = array();
	/**
	 * Screenshots associated with the table rows.
	 *
	 * @var array
	 */
	private $screenshots = array();

	/**
	 * Constructor for the Table class.
	 *
	 * @param array  $header The header of the table.
	 * @param object $report The report data for the table.
	 */
	public function __construct( $header, $report ) {
		$this->header = $header;
		$this->report = $report;
	}

	/**
	 * Adds a row to the table.
	 *
	 * @param array  $row The row data.
	 * @param string $screenshot_node_id The node ID for the screenshot.
	 *
	 * @return void
	 */
	public function add_row( $row, $screenshot_node_id = '' ) {
		$this->rows[]        = $row;
		$this->screenshots[] = $this->get_screenshot( $screenshot_node_id );
	}

	/**
	 * Renders the table.
	 *
	 * @return void
	 */
	public function render() {
		if ( empty( $this->rows ) ) {
			return;
		}
		?>
		<table class="sui-table">
			<tr>
				<?php foreach ( $this->header as $head_col ) : ?>
					<th><?php echo wp_kses_post( $head_col ); ?></th>
				<?php endforeach; ?>

				<?php if ( array_filter( $this->screenshots ) ) : ?>
					<th class="wds-lh-screenshot-th"><?php esc_html_e( 'Screenshot', 'wds' ); ?></th>
				<?php endif; ?>
			</tr>

			<?php foreach ( $this->rows as $index => $row_details ) : ?>
				<?php
				$row        = $row_details;
				$screenshot = \smartcrawl_get_array_value( $this->screenshots, $index );
				?>
				<tr>
					<?php foreach ( $row as $col ) : ?>
						<td><?php echo esc_html( $col ); ?></td>
					<?php endforeach; ?>

					<?php if ( $screenshot ) : ?>
						<td><?php echo wp_kses_post( $screenshot ); ?></td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
		</table>
		<?php
	}

	/**
	 * Gets the screenshot markup.
	 *
	 * @param string $node_id The node ID for the screenshot.
	 * @param int    $thumb_width The thumbnail width.
	 * @param int    $thumb_height The thumbnail height.
	 *
	 * @return false|string
	 */
	public function get_screenshot( $node_id, $thumb_width = 160, $thumb_height = 120 ) {
		$thumbnail = $this->get_screenshot_markup( $node_id, $thumb_width, $thumb_height );
		if ( ! $thumbnail ) {
			return '';
		}
		$screenshot = $this->get_screenshot_markup( $node_id, 600, 450 );
		ob_start();
		?>
		<div class="wds-lighthouse-thumbnail-container">
			<?php echo wp_kses_post( $thumbnail ); ?>
		</div>
		<div class="wds-lighthouse-screenshot-container">
			<?php echo wp_kses_post( $screenshot ); ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Gets the screenshot markup.
	 *
	 * @param string $node_id The node ID for the screenshot.
	 * @param int    $scaled_frame_width The scaled frame width.
	 * @param int    $scaled_frame_height The scaled frame height.
	 *
	 * @return false|string
	 */
	protected function get_screenshot_markup( $node_id, $scaled_frame_width, $scaled_frame_height ) {
		if ( empty( $node_id ) ) {
			return '';
		}

		$screenshot        = $this->report->get_screenshot();
		$screenshot_height = (int) $this->report->get_screenshot_height();
		$screenshot_width  = (int) $this->report->get_screenshot_width();
		if ( ! $screenshot || ! $screenshot_height || ! $screenshot_width ) {
			return '';
		}

		$node         = $this->report->get_screenshot_node( $node_id );
		$node_details = array(
			'top',
			'right',
			'bottom',
			'left',
			'width',
			'height',
		);
		foreach ( $node_details as $node_detail ) {
			if ( ! isset( $node[ $node_detail ] ) ) {
				return '';
			}
		}
		if ( empty( $node['width'] ) || empty( $node['height'] ) ) {
			return '';
		}

		$scale = $scaled_frame_width / $screenshot_width;

		$scaled_screenshot_height = $screenshot_height * $scale;
		if ( $scaled_screenshot_height < $scaled_frame_height ) {
			$scaled_frame_height = $scaled_screenshot_height;
		}

		$frame_height = ( $scaled_frame_height / $scaled_screenshot_height ) * $screenshot_height;
		$top_offset   = $this->calculate_top_offset( $node, $frame_height, $screenshot_height );

		ob_start();
		?>
		<div class="wds-lighthouse-screenshot"
			style="
				--element-screenshot-url: url(<?php echo esc_attr( $screenshot ); ?>);
				--element-screenshot-width: <?php echo esc_attr( $screenshot_width ); ?>px;
				--element-screenshot-height:<?php echo esc_attr( $screenshot_height ); ?>px;
				--element-screenshot-scaled-height: <?php echo esc_attr( $scaled_frame_height ); ?>px;
				--element-screenshot-scaled-width: <?php echo esc_attr( $scaled_frame_width ); ?>px;
				--element-screenshot-scale: <?php echo esc_attr( $scale ); ?>;
				--element-screenshot-top-offset: -<?php echo esc_attr( $top_offset ); ?>px;
				--element-screenshot-highlight-width: <?php echo esc_attr( $node['width'] ); ?>px;
				--element-screenshot-highlight-height: <?php echo esc_attr( $node['height'] ); ?>px;
				--element-screenshot-highlight-top: <?php echo esc_attr( $node['top'] ); ?>px;
				--element-screenshot-highlight-left: <?php echo esc_attr( $node['left'] ); ?>px;
				--element-screenshot-highlight-left-width: <?php echo esc_attr( $node['left'] + $node['width'] ); ?>px;
				--element-screenshot-highlight-top-height: <?php echo esc_attr( $node['top'] + $node['height'] ); ?>px;
				">
			<div class="wds-lighthouse-screenshot-inner">
				<div class="wds-lighthouse-screenshot-frame">
					<div class="wds-lighthouse-screenshot-image"></div>
					<div class="wds-lighthouse-screenshot-marker"></div>
					<div class="wds-lighthouse-screenshot-clip"></div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Calculates the top offset for the screenshot.
	 *
	 * @param array $node The node details.
	 * @param int   $frame_height The frame height.
	 * @param int   $screenshot_height The screenshot height.
	 *
	 * @return float|int|mixed
	 */
	private function calculate_top_offset( $node, $frame_height, $screenshot_height ) {
		if ( $node['height'] > $frame_height ) {
			// The highlighted element is too large to fit in the frame, show as much of it as possible.
			return $node['top'];
		}

		if ( $node['bottom'] < $frame_height ) {
			// The highlighted element is within the frame already, no offset necessary.
			return 0;
		}

		$ideal_space           = ( $frame_height - $node['height'] ) / 2; // Ideal space will center the element vertically.
		$space_available_under = $screenshot_height - $node['bottom'];
		if ( $space_available_under < $ideal_space ) {
			return $screenshot_height - $frame_height;
		}

		$space_available_over = $screenshot_height - $space_available_under - $node['height'];
		if ( $space_available_over < $ideal_space ) {
			return 0;
		}

		return $node['top'] - $ideal_space; // Center the element.
	}

	/**
	 * Gets the table header.
	 *
	 * @return array
	 */
	public function get_header() {
		return $this->header;
	}

	/**
	 * Gets the table rows.
	 *
	 * @return array
	 */
	public function get_rows() {
		return $this->rows;
	}
}