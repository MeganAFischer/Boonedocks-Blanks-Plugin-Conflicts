<?php
/**
 * Manages Deactivation Survey.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Admin;

use SmartCrawl\Admin\Settings\Admin_Settings;
use SmartCrawl\Controllers\Controller;
use SmartCrawl\Settings;
use SmartCrawl\Controllers\Assets;
use SmartCrawl\Singleton;

/**
 * Survey controller
 */
class Survey extends Controller {

	use Singleton;

	const ID = 'wds-admin-survey';

	/**
	 * Initialization method.
	 *
	 * @return void
	 */
	protected function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'wpmudev_dashboard_ui_after_footer', array( $this, 'render_survey_modal' ) );
		add_action( 'pre_current_active_plugins', array( $this, 'render_survey_modal' ) );
		add_action( 'wp_ajax_smartcrawl_count_from_survey', array( $this, 'count_from_survey' ) );
	}

	/**
	 * Registers script and style files.
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		if ( self::is_wpmudev_plugins_page() || self::is_wp_plugins_page() ) {
			\smartcrawl_enqueue_style( self::ID, 'survey' );

			$deps = array( 'jquery' );

			if ( self::is_wpmudev_plugins_page() ) {
				$deps[] = 'wpmudev-dashboard-admin-js';
			}

			if ( self::is_wp_plugins_page() ) {
				\smartcrawl_enqueue_style( Assets::APP_CSS, 'app' );

				\smartcrawl_enqueue_script(
					Assets::SUI_JS,
					'shared-ui',
					array(
						'jquery',
						'clipboard',
					)
				);

				$deps[] = Assets::SUI_JS;
			}

			\smartcrawl_enqueue_script( self::ID, self::ID, $deps );

			wp_localize_script(
				self::ID,
				'_wds_survey',
				array(
					'settings_url' => add_query_arg( 'survey', 1, Admin_Settings::admin_url( Settings::TAB_SETTINGS ) ),
					'nonce'        => wp_create_nonce( 'wds-survey-nonce' ),
				)
			);
		}
	}

	/**
	 * Renders survey modal container.
	 *
	 * @return void
	 */
	public function render_survey_modal() {
		?>

		<div class="<?php echo esc_attr( \smartcrawl_sui_class() ); ?>">
			<div class="sui-wrap">
				<div id="wds-survey-wrap"></div>
			</div>
		</div>

		<?php
	}

	/**
	 * Counts clicking on activate specific modules link on survey modal.
	 *
	 * @return void
	 */
	public function count_from_survey() {
		$count = Settings::get_specific_options( 'wds-from-survey', 0 );

		++$count;

		Settings::update_specific_options( 'wds-from-survey', $count );

		wp_send_json_success();
	}

	/**
	 * Checks if current page is WPMU DEV Plugins page.
	 *
	 * @return bool
	 */
	private function is_wpmudev_plugins_page() {
		global $pagenow;

		if ( 'admin.php' !== $pagenow ) {
			return false;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return 'wpmudev-plugins' === $page;
	}

	/**
	 * Checks if current page is WordPress Plugins page.
	 *
	 * @return bool
	 */
	private function is_wp_plugins_page() {
		global $pagenow;

		return 'plugins.php' === $pagenow;
	}
}