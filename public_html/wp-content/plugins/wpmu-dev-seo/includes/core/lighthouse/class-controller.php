<?php
/**
 * Controller class for managing Lighthouse checks and related actions.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Lighthouse;

use SmartCrawl\Admin\Settings\Admin_Settings;
use SmartCrawl\Settings;
use SmartCrawl\Singleton;
use SmartCrawl\Controllers;
use SmartCrawl\Models\User;
use SmartCrawl\Services\Service;

/**
 * Class Controller
 *
 * TODO: add more checks to lighthouse
 */
class Controller extends Controllers\Controller {

	use Singleton;

	const ERROR_RESULT_NOT_FOUND = 30;

	/**
	 * Initializes the controller by adding necessary actions.
	 *
	 * @return void
	 */
	protected function init() {
		add_action(
			'wp_ajax_wds-lighthouse-run',
			array(
				$this,
				'run_lighthouse',
			)
		);
		add_action(
			'wp_ajax_wds-lighthouse-start-test',
			array(
				$this,
				'start_lighthouse_test',
			)
		);
		add_action(
			'wds_plugin_update',
			array(
				$this,
				'apply_checkup_schedule_to_lighthouse',
			)
		);
		add_action(
			'smartcrawl_admin_notices',
			array(
				$this,
				'checkup_removal_notice',
			)
		);
	}

	/**
	 * Displays a notice about the removal of the checkup feature.
	 *
	 * @return void
	 */
	public function checkup_removal_notice() {
		$key                  = 'wds_checkup_removed_218';
		$dismissed_messages   = get_user_meta( get_current_user_id(), 'wds_dismissed_messages', true );
		$is_message_dismissed = \smartcrawl_get_array_value( $dismissed_messages, $key ) === true;
		$is_version_218       = version_compare( SMARTCRAWL_VERSION, '2.18.0', '=' );
		if (
			$is_message_dismissed
			||
			! $is_version_218
			||
			! current_user_can( 'manage_options' )
		) {
			return;
		}

		$health_admin_url = Admin_Settings::admin_url( Settings::TAB_HEALTH );
		?>
		<div
			class="notice-info notice is-dismissible wds-native-dismissible-notice"
			data-message-key="<?php echo esc_attr( $key ); ?>">
			<p style="margin-bottom: 15px;">
				<?php
				printf(
					/* translators: %s: Current user's first name */
					esc_html__( 'Heads up, %s! SmartCrawl’s SEO Checkup functionality has been removed in favor of SEO Audits powered by Google Lighthouse. We’ve automatically migrated your SEO Checkup settings to Lighthouse SEO Audit.', 'wds' ),
					esc_html( User::current()->get_first_name() )
				);
				?>
			</p>
			<a href="<?php echo esc_attr( $health_admin_url ); ?>"
				class="button button-primary">
				<?php esc_html_e( 'Check Out SEO Audits', 'wds' ); ?>
			</a>
			<a href="#"
				class="wds-native-dismiss"><?php esc_html_e( 'Dismiss', 'wds' ); ?></a>
			<p></p>
		</div>
		<?php
	}

	/**
	 * Starts the Lighthouse test.
	 *
	 * @return void
	 */
	public function start_lighthouse_test() {
		$request_data = $this->get_request_data();
		if ( empty( $request_data ) ) {
			wp_send_json_error();
		}
		/**
		 * Service lighthouse instance.
		 *
		 * @var \SmartCrawl\Services\Lighthouse $lighthouse Lighthouse service.
		 */
		$lighthouse = Service::get( Service::SERVICE_LIGHTHOUSE );
		$lighthouse->clear_last_report();
		$lighthouse->stop();
		$lighthouse->start();

		wp_send_json_success();
	}

	/**
	 * Runs the Lighthouse check.
	 *
	 * @return void
	 */
	public function run_lighthouse() {
		$request_data = $this->get_request_data();
		if ( empty( $request_data ) ) {
			wp_send_json_error();
		}

		/**
		 *  Service lighthouse instance.
		 *
		 * @var \SmartCrawl\Services\Lighthouse $lighthouse
		 */
		$lighthouse = Service::get( Service::SERVICE_LIGHTHOUSE );
		$start_time = $lighthouse->get_start_time();
		if ( ! $start_time ) {
			$lighthouse->start();
			wp_send_json_success( array( 'finished' => false ) );
		}

		$current_time = current_datetime();
		$now          = $current_time->getTimestamp() + $current_time->getOffset();

		if ( $now < $start_time + 15 ) {
			// Not enough time has passed, buy more time.
			wp_send_json_success( array( 'finished' => false ) );
		}

		if ( $now >= $start_time + 90 ) {
			// Too much time has passed, something might be wrong, force user to start over.
			$lighthouse->stop();
			$lighthouse->clear_last_report();
			$lighthouse->set_error(
				'timeout',
				esc_html__( 'We were not able to get results for your site', 'wds' )
			);
			wp_send_json_success( array( 'finished' => true ) );
		}

		$lighthouse->refresh_report();
		$last_report = $lighthouse->get_last_report();
		if (
			$last_report->get_error_code() === self::ERROR_RESULT_NOT_FOUND
			|| ! $last_report->is_fresh()
		) {
			// Let's wait a little longer for the results to become available.
			wp_send_json_success( array( 'finished' => false ) );
		}

		$lighthouse->stop();
		wp_send_json_success( array( 'finished' => true ) );
	}

	/**
	 * Retrieves and sanitizes the request data.
	 *
	 * @return array
	 */
	private function get_request_data() {
		return isset( $_POST['_wds_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wds_nonce'] ) ), 'wds-lighthouse-nonce' )
			? stripslashes_deep( $_POST )
			: array();
	}

	/**
	 * Applies the checkup schedule to Lighthouse.
	 *
	 * TODO: remove when enough time has passed
	 *
	 * @return void
	 */
	public function apply_checkup_schedule_to_lighthouse() {
		$version_with_checkup     = '2.17.1';
		$last_version             = \SmartCrawl\SmartCrawl::get_last_version();
		$last_version_had_checkup = version_compare( $last_version, $version_with_checkup, '<=' );
		$option_id                = 'wds_checkup_removed';
		$schedule_already_applied = get_option( $option_id, false );
		$checkup_options          = get_option( 'wds_checkup_options' );

		if (
			$last_version_had_checkup
			&&
			! $schedule_already_applied
			&&
			! empty( $checkup_options )
		) {
			$test_mode_was_checkup = \smartcrawl_get_array_value( get_option( 'wds_health_options' ), 'health-test-mode' ) === 'seo-checkup';
			if ( $test_mode_was_checkup ) {
				$cron_enabled = (bool) \smartcrawl_get_array_value( $checkup_options, 'checkup-cron-enable' );
				if ( $cron_enabled ) {
					// Checkup cron was enabled in the last version so from now on we want the lighthouse report to be sent to all the checkup recipients.

					$reporting_frequency = \smartcrawl_get_array_value( $checkup_options, 'checkup-frequency' );
					$reporting_dow       = \smartcrawl_get_array_value( $checkup_options, 'checkup-dow' );
					$reporting_tod       = \smartcrawl_get_array_value( $checkup_options, 'checkup-tod' );
					$recipients          = $this->get_checkup_recipients();

					$lighthouse_options = wp_parse_args(
						array(
							Options::CRON_ENABLE         => true,
							Options::REPORTING_FREQUENCY => $reporting_frequency,
							Options::REPORTING_DOW       => $reporting_dow,
							Options::REPORTING_TOD       => $reporting_tod,
							Options::RECIPIENTS          => $recipients,
							Options::REPORTING_CONDITION_ENABLED => false,
							Options::REPORTING_DEVICE    => 'both',
						),
						Options::get_options()
					);
				} else {
					// No emails were being sent in the last version, so we don't want lighthouse emails to start up suddenly, disable lighthouse.

					$lighthouse_options = wp_parse_args(
						array(
							Options::CRON_ENABLE => false,
						),
						Options::get_options()
					);
				}
				update_option( Options::OPTION_ID, $lighthouse_options );
			}

			update_option( $option_id, true );
		}
	}

	/**
	 * Retrieves the checkup recipients.
	 *
	 * @return array
	 */
	public function get_checkup_recipients() {
		$email_recipients = array();
		$options          = Settings::get_specific_options( 'wds_checkup_options' );
		$new_recipients   = empty( $options['checkup-email-recipients'] )
			? array()
			: $options['checkup-email-recipients'];
		$old_recipients   = empty( $options['email-recipients'] )
			? array()
			: $options['email-recipients'];

		foreach ( $old_recipients as $user_id ) {
			if ( ! is_numeric( $user_id ) ) {
				continue;
			}
			$old_recipient = $this->get_email_recipient( $user_id );
			if ( $this->recipient_exists( $old_recipient, $new_recipients ) ) {
				continue;
			}

			$email_recipients[] = $old_recipient;
		}

		return array_merge(
			$email_recipients,
			$new_recipients
		);
	}

	/**
	 * Retrieves the email recipient details.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array
	 */
	private function get_email_recipient( $user_id = false ) {
		if ( $user_id ) {
			$user = User::get( $user_id );
		} else {
			$user = User::owner();
		}

		return array(
			'name'  => $user->get_display_name(),
			'email' => $user->get_email(),
		);
	}

	/**
	 * Checks if a recipient exists in the recipient array.
	 *
	 * @param array $recipient Recipient details.
	 * @param array $recipient_array Array of recipients.
	 *
	 * @return bool
	 */
	private function recipient_exists( $recipient, $recipient_array ) {
		$emails = array_column( $recipient_array, 'email' );
		$needle = (string) \smartcrawl_get_array_value( $recipient, 'email' );

		return in_array( $needle, $emails, true );
	}
}