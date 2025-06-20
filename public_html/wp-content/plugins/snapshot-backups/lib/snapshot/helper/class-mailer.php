<?php // phpcs:ignore
/**
 * Snapshot Mail helper class
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Helper;

use WPMUDEV\Snapshot4\Helper\Settings;
use WPMUDEV\Snapshot4\Helper\Template;
use WPMUDEV\Snapshot4\Model\Settings as Model_Settings;

/**
 * Provides a helper class for sending emails using Snapshot templates.
 *
 * The Mailer class is responsible for handling the email sending process,
 * including setting up the email headers, body, and subject. It uses the
 * Template class to render the email templates.
 *
 * @package WPMUDEV\Snapshot4\Helper
 */
class Mailer extends Template {

	/**
	 * Default arguments for the email template.
	 *
	 * @var array
	 */
	private $default_args = array();

	/**
	 * Constructs a new instance of the Mailer class.
	 */
	public function __construct() {
		$this->default_args = $this->get_default_args();
	}

	/**
	 * Get default arguments for the template.
	 *
	 * @return array
	 */
	public function get_default_args() {
		$site_url = get_site_url();
		$site     = wp_parse_url( $site_url, PHP_URL_HOST );

		$from      = 'noreply@' . $site;
		$from_name = 'Snapshot';
		$header    = "From: $from_name <$from>";

		$from_header = apply_filters( 'snapshot_email_from_header', $header );

		return array(
			'plugin_custom_name' => Settings::get_brand_name(),
			'is_branding_hidden' => Settings::get_branding_hide_doc_link(),
			'assets'             => new Assets(),
			'site'               => $site,
			'site_url'           => $site_url,
			'mail_header'        => $from_header,
			'subject'            => '',
			'model'              => null,
		);
	}

	/**
	 * Generates the email header.
	 *
	 * @param string $mail_file The name of the email template file.
	 * @param array  $args      Additional arguments to pass to the template.
	 * @return string The generated email header.
	 */
	public function header( $mail_file, $args = array() ) {
		$args = wp_parse_args( $args, $this->default_args );

		$args['template'] = $mail_file;

		ob_start();
			$this->render( 'mail/common/header', $args );
		return ob_get_clean();
	}

	/**
	 * Generates the email footer.
	 *
	 * @param string $mail_file The name of the email template file.
	 * @param array  $args      Additional arguments to pass to the template.
	 * @return string The generated email footer.
	 */
	public function footer( $mail_file, $args = array() ) {
		$args = wp_parse_args( $args, $this->default_args );

		$args['template'] = $mail_file;

		ob_start();
			$this->render( 'mail/common/footer', $args );
		return ob_get_clean();
	}

	/**
	 * Extracts the user details from the provided arguments.
	 *
	 * @param array $args The arguments containing the user details.
	 * @return array An array with the user's email and name.
	 */
	private function extract_user_details( $args ) {
		$model = isset( $args['model'] ) ? $args['model'] : null;

		if (
			! is_null( $model ) &&
			isset( $model->get( 'export' )['email_account'] ) &&
			! empty( $model->get( 'export' )['email_account'] )
		) {
			$mail_to = $model->get( 'export' )['email_account'];
		} else {
			$mail_to = Model_Settings::get_auth_user_email();
		}

		if ( empty( $mail_to ) ) {
			Log::error( __( 'Unable to send email because user\'s email is empty', 'snapshot' ) );
			return new \WP_Error( 'empty_wdp_un_auth_user', 'unable to send email because user\'s email is empty' );
		}

		if (
			! is_null( $model ) &&
			isset( $model->get( 'export' )['display_name'] ) &&
			! empty( $model->get( 'export' )['display_name'] )
		) {
			$name = $model->get( 'export' )['display_name'];
		} else {
			$wdp_un_profile_data = get_site_option( 'wdp_un_profile_data' );
			$name                = isset( $wdp_un_profile_data['profile']['name'] ) ? $wdp_un_profile_data['profile']['name'] : '';
		}

		return array(
			'email' => $mail_to,
			'name'  => $name,
		);
	}

	/**
	 * Sends an email using the provided template and arguments.
	 *
	 * @param string $mail_file The name of the email template file.
	 * @param array  $args      Additional arguments to pass to the template.
	 * @return bool True if the email was sent successfully, false otherwise.
	 */
	public function send( $mail_file, $args = array() ) {
		$args = wp_parse_args( $args, $this->default_args );

		$details = $this->extract_user_details( $args );

		if ( isset( $args['recipient_email'] ) ) {
			$mail_to = $args['recipient_email'];
		} else {
			$mail_to = $details['email'];
		}

		if ( ! isset( $args['name'] ) ) {
			$args['name'] = $details['name'];
		}

		ob_start();
			$this->render( $mail_file, $args );
		$body = ob_get_clean();

		$content  = $this->header( $mail_file, $args );
		$content .= $body;
		$content .= $this->footer( $mail_file, $args );

		$subject = $args['subject'];

		return wp_mail( $mail_to, $subject, $content, array( 'Content-Type: text/html', $args['mail_header'] ) );
	}
}