<?php
/**
 * Initializes breadcrumbs functionality.
 *
 * @since   3.5.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Modules\Advanced\Seomoz;

use SmartCrawl\Admin\Module_Settings;
use SmartCrawl\Admin\Settings\Admin_Settings;
use SmartCrawl\Controllers;
use SmartCrawl\Singleton;

/**
 * Breadcrumbs class.
 */
class Controller extends Controllers\Submodule_Controller {


	use Singleton;

	/**
	 * Cron Controller for Seomoz.
	 *
	 * @var Cron
	 */
	private $cron;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		$this->cron = Cron::get();

		$this->module_title = __( 'Moz', 'wds' );
		$this->event_name   = 'Moz';
	}

	/**
	 * Includes methods that runs always.
	 *
	 * @return void
	 */
	protected function always() {
		parent::always();

		if ( $this->should_run() ) {
			return;
		}

		$this->cron->stop();
	}

	/**
	 * Initialization method.
	 *
	 * @return void
	 */
	protected function init() {
		parent::init();

		$this->cron->set_options( $this->options );
		$this->cron->run();

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'wp_ajax_smartcrawl_update_moz_conn', array( $this, 'update_moz_connection' ) );
	}

	/**
	 * Ajax handler to connect to Moz account.
	 *
	 * @return void
	 */
	public function update_moz_connection() {
		if ( ! isset( $_POST['_wds_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wds_nonce'] ) ), 'wds-admin-nonce' ) ) {
			wp_send_json_error();
		}

		$old_options = $this->options;

		if ( isset( $_POST['reset'] ) && sanitize_text_field( wp_unslash( $_POST['reset'] ) ) ) {
			unset( $this->options['access_id'] );
			unset( $this->options['secret_key'] );

			$this->parent->update_option( $this->module_name, $this->options );

			do_action( 'smartcrawl_update_seomoz_connection', $old_options, $this->options );

			wp_send_json_success();
		}

		if ( ! isset( $_POST['access_id'] ) || ! isset( $_POST['secret_key'] ) ) {
			wp_send_json_error();
		}

		$this->options['access_id']  = sanitize_text_field( wp_unslash( $_POST['access_id'] ) );
		$this->options['secret_key'] = sanitize_text_field( wp_unslash( $_POST['secret_key'] ) );

		$this->parent->update_option( $this->module_name, $this->options );

		$data = $this->get_metrics_args( get_bloginfo( 'url' ) );

		if ( ! $data ) {
			wp_send_json_error();
		}

		do_action( 'smartcrawl_update_seomoz_connection', $old_options, $this->options );

		wp_send_json_success( array( 'metrics' => $data ) );
	}

	/**
	 * Adds a box to the main column on the Post and Page edit screens.
	 *
	 * @return void
	 */
	public function add_meta_boxes() {
		if ( empty( $this->options['access_id'] ) || empty( $this->options['secret_key'] ) ) {
			return;
		}

		$show = \user_can_see_urlmetrics_metabox();

		foreach ( get_post_types() as $post_type ) {
			if ( $show ) {
				add_meta_box(
					'wds_seomoz_urlmetrics',
					sprintf(
						// Translators: %s is the name of the plugin.
						__( 'Moz URL Metrics - %s', 'wds' ),
						\smartcrawl_get_plugin_title()
					),
					array( $this, 'urlmetrics_box' ),
					$post_type,
					'normal',
					'high'
				);
			}
		}
	}

	/**
	 * Prints the box content.
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return void
	 */
	public function urlmetrics_box( $post ) {
		?>
		<div class="<?php echo esc_attr( \smartcrawl_sui_class() ); ?>">
			<div class="<?php \smartcrawl_wrap_class( 'wds-metabox' ); ?>">
				<div class="wds-metabox-section">
					<?php
					$this->render_metrics(
						get_permalink( $post->ID ),
						'urlmetrics-metabox'
					);
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders a view with moz metrics data.
	 *
	 * @param string $target_url Target URL.
	 * @param string $view View name.
	 *
	 * @return void
	 */
	public function render_metrics( $target_url, $view ) {
		if ( empty( $this->options['access_id'] ) || empty( $this->options['secret_key'] ) ) {
			return;
		}

		$data = $this->get_metrics_args( $target_url );

		if ( $data ) {
			Module_Settings::get()->output_view( $view, $data );
		} else {
			$api        = new API( $this->options['access_id'], $this->options['secret_key'] );
			$urlmetrics = $api->urlmetrics( $target_url );
			$error      = $this->get_specific_error( $urlmetrics );
			$message    = sprintf(
				'%s %s',
				esc_html__( 'We were unable to retrieve data from the Moz API.', 'wds' ),
				$error
			);

			Module_Settings::get()->output_view(
				'notice',
				array(
					'class'   => 'sui-notice-error',
					'message' => $message,
				)
			);
		}
	}

	/**
	 * Returns Moz metrics data.
	 *
	 * @param string $target_url Target URL.
	 *
	 * @return array|false
	 */
	private function get_metrics_args( $target_url ) {
		if ( empty( $this->options['access_id'] ) || empty( $this->options['secret_key'] ) ) {
			return false;
		}

		$access_id  = $this->options['access_id'];
		$secret_key = $this->options['secret_key'];
		$target_url = preg_replace( '!http(s)?:\/\/!', '', $target_url );
		$api        = new API( $access_id, $secret_key );
		$urlmetrics = $api->urlmetrics( $target_url );

		if ( ! is_object( $urlmetrics ) || ! $api->is_response_valid( $urlmetrics ) ) {
			return false;
		}

		$attribution = str_replace( '/', '%252F', untrailingslashit( $target_url ) );
		$attribution = "https://moz.com/researchtools/ose/links?site={$attribution}";

		return array(
			'attribution' => $attribution,
			'urlmetrics'  => $urlmetrics,
		);
	}

	/**
	 * Get the error.
	 *
	 * @param object $response Response.
	 *
	 * @return string
	 */
	private function get_specific_error( $response ) {
		switch ( API::get_error_type( $response ) ) {
			case 400:
				return esc_html__( "If you've recently created an account, allow 24 hours for your first data to arrive. If you are an existing user, please reset your Moz API credentials to fix this issue.", 'wds' );

			default:
				return isset( $response->error_message ) ? $response->error_message : '';
		}
	}

	/**
	 * Sanitizes submitted options
	 *
	 * @param array $input Raw input.
	 *
	 * @return bool True if sanitized successfully, otherwise false.
	 */
	public function sanitize_options( $input ) {
		$old_options = $this->options;

		if ( isset( $input['active'] ) ) {
			$active = boolval( $input['active'] );

			if ( empty( $this->options['active'] ) || $active !== $this->options['active'] ) {
				$this->options['active'] = $active;

				return true;
			}

			unset( $input['active'] );
		}

		if ( empty( $input ) ) {
			return true;
		}

		if ( isset( $input['reset'] ) ) {
			$this->options['access_id']  = '';
			$this->options['secret_key'] = '';
		} else {
			if ( isset( $input['access_id'] ) ) {
				$this->options['access_id'] = sanitize_text_field( $input['access_id'] );
			}

			if ( isset( $input['secret_key'] ) ) {
				$this->options['secret_key'] = sanitize_text_field( $input['secret_key'] );
			}
		}

		return true;
	}

	/**
	 * Returns localize script data.
	 *
	 * @return array
	 */
	public function localize_script_args() {
		$args = array(
			'active'  => ! empty( $this->options['active'] ),
			'metrics' => $this->get_metrics_args( get_bloginfo( 'url' ) ),
			'options' => array(
				'access_id'  => isset( $this->options['access_id'] ) ? $this->options['access_id'] : '',
				'secret_key' => isset( $this->options['secret_key'] ) ? $this->options['secret_key'] : '',
			),
		);

		return $args;
	}

	/**
	 * Outputs submodule content to dashboard widget.
	 *
	 * @return void
	 */
	public function render_dashboard_content() {
		$is_connected = ! empty( $this->options['access_id'] ) && ! empty( $this->options['secret_key'] );
		$module_url   = Admin_Settings::admin_url( $this->parent->module_name ) . '&tab=' . $this->module_name;
		?>

		<div
			class="wds-separator-top wds-draw-left-padded <?php echo $this->should_run() && $is_connected ? 'wds-space-between' : ''; ?>">
			<small><strong><?php esc_html_e( 'Moz Integration', 'wds' ); ?></strong></small>

			<?php if ( $this->should_run() ) : ?>

				<?php if ( $is_connected ) : ?>

					<a
						href="<?php echo esc_attr( $module_url ); ?>"
						class="sui-button sui-button-ghost"
					>
						<span class="sui-icon-eye" aria-hidden="true"></span>
						<?php esc_html_e( 'View Report', 'wds' ); ?>
					</a>

				<?php else : ?>
					<p>
						<small><?php esc_html_e( 'Moz provides reports that tell you how your site stacks up against the competition with all of the important SEO measurement tools.', 'wds' ); ?></small>
					</p>
					<a
						href="<?php echo esc_attr( $module_url ); ?>"
						aria-label="<?php esc_html_e( 'Connect your Moz account', 'wds' ); ?>"
						class="sui-button sui-button-blue"
					>
						<?php esc_html_e( 'Connect', 'wds' ); ?>
					</a>
				<?php endif; ?>

			<?php else : ?>

				<p>
					<small><?php esc_html_e( 'Automatically redirect traffic from one URL to another.', 'wds' ); ?></small>
				</p>
				<button
					type="button"
					data-module="<?php echo esc_attr( $this->parent->module_id ); ?>"
					data-submodule="<?php echo esc_attr( $this->module_id ); ?>"
					class="wds-activate-submodule wds-disabled-during-request sui-button sui-button-blue">
					<span class="sui-loading-text"><?php esc_html_e( 'Activate', 'wds' ); ?></span>
					<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
				</button>

			<?php endif; ?>

		</div>

		<?php
	}
}