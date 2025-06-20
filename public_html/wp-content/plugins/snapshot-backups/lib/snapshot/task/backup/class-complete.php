<?php // phpcs:ignore
/**
 * Completed backup email notifications.
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Task\Backup;

use WPMUDEV\Snapshot4\Task;
use WPMUDEV\Snapshot4\Helper\Log;
use WPMUDEV\Snapshot4\Helper\Mailer;

/**
 * Finish backup task class
 */
class Complete extends Task {

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
				$args['backup_id'],
			);
		}
	}

	/**
	 * Send email to specified recipient when a backup completes
	 *
	 * @param string $email     Recipient email address.
	 * @param string $name      Recipient first name.
	 * @param string $backup_id Backup ID.
	 */
	private function send( $email, $name, $backup_id ) {
		$site_url  = get_site_url();
		$site_host = wp_parse_url( $site_url, PHP_URL_HOST );

		/* translators: %s - website URL */
		$subject = sprintf( __( 'The backup for %s was created and stored successfully.', 'snapshot' ), $site_host );

		$params = array(
			'name'            => $name,
			'backup_url'      => network_admin_url() . 'admin.php?page=snapshot-backups#backups-' . $backup_id,
			'subject'         => $subject,
			'recipient_email' => $email,
		);

		$mailer = new Mailer();
		$result = $mailer->send( 'mail/backup-complete', $params );

		if ( ! $result ) {
			/* translators: %s - "mail to" address */
			Log::error( sprintf( __( 'Unable to send email to %s', 'snapshot' ), $email ), array(), $backup_id );
		}
	}
}