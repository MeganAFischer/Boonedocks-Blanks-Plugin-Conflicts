<?php // phpcs:ignore
/**
 * Completed backup email notifications.
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Task\Backup\Export;

use WPMUDEV\Snapshot4\Helper\Datetime;
use WPMUDEV\Snapshot4\Task;
use WPMUDEV\Snapshot4\Helper\Log;
use WPMUDEV\Snapshot4\Helper\Mailer;

/**
 * Finish backup task class
 */
class Fail extends Task {

	/**
	 * Send email notifications when a backup completes
	 *
	 * @param array $args Task args.
	 */
	public function apply( $args = array() ) {
		foreach ( $args['recipients'] as $recipient ) {
			$this->send(
				$recipient['email'],
				$recipient['name'],
				$args
			);
		}
	}

	/**
	 * Send email to specified recipient when a backup completes
	 *
	 * @param string $email     Recipient email address.
	 * @param string $name      Recipient first name.
	 * @param array  $args       Backup details.
	 */
	private function send( $email, $name, $args ) {
		$site_url  = get_site_url();
		$site_host = wp_parse_url( $site_url, PHP_URL_HOST );

		/* translators: %s - website URL */
		$subject = sprintf( __( 'The backup export for %s failed.', 'snapshot' ), $site_host );

		$date = $args['backup_date'];
		$date = Datetime::format( strtotime( $args['backup_date'] ) );

		$params = array(
			'name'            => $name,
			'backup_url'      => network_admin_url() . 'admin.php?page=snapshot-backups#backups-' . $args['backup_id'],
			'subject'         => $subject,
			'recipient_email' => $email,
			'destination'     => $args['destination_name'],
			'service_error'   => $args['service_error'],
			'backup_id'       => $args['backup_id'],
			'date'            => $date,
		);

		$mailer = new Mailer();
		$result = $mailer->send( 'mail/backup/export-fail', $params );

		if ( ! $result ) {
			/* translators: %s - "mail to" address */
			Log::error( sprintf( __( 'Unable to send email to %s', 'snapshot' ), $email ), array(), $args['backup_id'] );
		}
	}
}