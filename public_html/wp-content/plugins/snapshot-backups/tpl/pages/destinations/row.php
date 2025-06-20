<?php // phpcs:ignore
/**
 * Row with destination details.
 *
 * @package snapshot
 */

use WPMUDEV\Snapshot4\Model\Request\Destination\Onedrive;
use WPMUDEV\Snapshot4\Model\Request\Destination\Googledrive;
use WPMUDEV\Snapshot4\Model\Request\Destination\Dropbox;

$failed_exports_list = get_site_option( 'snapshot_failed_third_party_destination_exports', array() );
$google_drives       = isset( $failed_exports_list['google_drive'] ) ? $failed_exports_list['google_drive'] : array();
$onedrives_list      = isset( $failed_exports_list['onedrive'] ) ? $failed_exports_list['onedrive'] : array();
$icon_class          = "row-icon-{$tpd_type}";

if ( isset( $meta ) ) {
	if ( ! is_array( $meta ) ) {
		$meta = json_decode( $meta, true );
	}
} else {
	$meta = array();
}

switch ( $tpd_type ) {
	case 'aws':
		$icon_tooltip = __( 'S3/Amazon', 'snapshot' );
		break;
	case 'wasabi':
		$icon_tooltip = __( 'S3/Wasabi', 'snapshot' );
		break;
	case 'digitalocean':
		$icon_tooltip = __( 'S3/DigitalOcean', 'snapshot' );
		break;
	case 'backblaze':
		$icon_tooltip = __( 'S3/Backblaze', 'snapshot' );
		break;
	case 'googlecloud':
		$icon_tooltip = __( 'S3/Google Cloud', 'snapshot' );
		break;
	case 'gdrive':
		$icon_tooltip  = __( 'Google Drive', 'snapshot' );
		$email         = $tpd_email_gdrive;
		$tpd_accesskey = $tpd_acctoken_gdrive;
		$tpd_secretkey = $tpd_retoken_gdrive;
		break;
	case 's3_other':
		$icon_tooltip = __( 'S3/Other', 'snapshot' );
		$endpoint     = $meta['tpd_endpoint'];
		break;
	case 'linode':
		$icon_tooltip = __( 'Linode', 'snapshot' );
		$endpoint     = $meta['tpd_endpoint'];
		break;
	case 'dropbox':
		$icon_tooltip  = __( 'Dropbox', 'snapshot' );
		$tpd_accesskey = $tpd_acctoken_gdrive;
		$tpd_secretkey = $tpd_retoken_gdrive;
		$email         = $tpd_email_gdrive;
		break;
	case 'ftp':
		$icon_tooltip = __( 'FTP', 'snapshot' );
		$email        = $meta['ftp_host'];
		$view_url     = '#';
		break;
	case 'sftp':
		$icon_tooltip = __( 'SFTP', 'snapshot' );
		$icon_class   = 'row-icon-ftp';
		$email        = $meta['ftp_host'];
		$view_url     = '#';
		break;
	case 'onedrive':
		$icon_tooltip  = __( 'OneDrive', 'snapshot' );
		$tpd_accesskey = $tpd_acctoken_gdrive;
		$tpd_secretkey = $tpd_retoken_gdrive;
		$email         = $tpd_email_gdrive;
		$view_url      = ( isset( $meta['onedrive_weburl'] ) ) ? $meta['onedrive_weburl'] : '#';
		break;
	default:
		$icon_tooltip = __( 'S3/', 'snapshot' ) . $tpd_type;
		$email        = '';
		$endpoint     = '';
		break;
}//end switch


$is_linode = false;
if ( 's3_other' === $tpd_type && isset( $meta['ftp_host'] ) && 'linode' === $meta['ftp_host'] ) {
	$is_linode = true;
	$tpd_type  = 'linode';
	$icon_tooltip = __( 'S3/Linode', 'snapshot' );
}

if ( isset( $endpoint ) && '' !== $endpoint ) {
	if ( str_contains( $endpoint, 'linodeobjects' ) ) {
		$is_linode = true;
		$tpd_type  = 'linode';
		$icon_tooltip = __( 'S3/Linode', 'snapshot' );
	}
}

if ( '' === $tpd_path || 'undefined' === $tpd_path || null === $tpd_path ) {
	$tpd_path = '/';
}
if ( 'azure' === $tpd_type ) {
	$path_parts = explode( '/', $tpd_path, 2 );
	$tpd_bucket = $path_parts[0];
}

$row_class = "destination-type-{$tpd_type}";
if ( 'sftp' === $tpd_type ) {
	$row_class = 'destination-type-ftp';
}

$is_failed_export_gdrive = ! empty( $google_drives ) && in_array( $tpd_id, $google_drives, true );

$oauth_link = '#';
if ( 'gdrive' === $tpd_type && $is_failed_export_gdrive ) {
	$gdrive_oauth_link = Googledrive::create_oauth_link(
		array(
			'tpd_id' => $tpd_id,
			'reauth' => 'yes',
		)
	);
}

$onedrive_ids = get_site_option( 'snapshot_onedrive_destination_suspended', array() );
$dropbox_ids  = get_site_option( 'snapshot_dropbox_destination_suspended', array() );

if ( ! $onedrive_ids ) {
	$onedrive_ids = array();
}

if ( ! $dropbox_ids ) {
	$dropbox_ids = array();
}

$reauthorized = array();
$unauthorized = array();
if ( isset( $onedrive_ids['reauthorized'] ) && is_array( $onedrive_ids['reauthorized'] ) ) {
	$reauthorized = $onedrive_ids['reauthorized'];
}

if ( isset( $onedrive_ids['unauthorized'] ) && is_array( $onedrive_ids['unauthorized'] ) ) {
	$unauthorized = $onedrive_ids['unauthorized'];
}

$dropbox_reauthorized = array();
$dropbox_unauthorized = array();
if ( isset( $dropbox_ids['reauthorized'] ) && is_array( $dropbox_ids['reauthorized'] ) ) {
	$dropbox_reauthorized = $dropbox_ids['reauthorized'];
}

if ( isset( $dropbox_ids['unauthorized'] ) && is_array( $dropbox_ids['unauthorized'] ) ) {
	$dropbox_unauthorized = $dropbox_ids['unauthorized'];
}


$onedrive_oauth_link = '';
$dropbox_oauth_link  = '';
if ( $is_suspended ) {
	if ( 'onedrive' === $tpd_type ) {
		if (
			! in_array( $tpd_id, $reauthorized, true ) &&
			! in_array( $tpd_id, $unauthorized, true )
		) {
			array_push( $unauthorized, $tpd_id );
		}

		$row_class          .= ' onedrive--destination__expired';
		$onedrive_oauth_link = Onedrive::create_oauth_link(
			array(
				'tpd_id' => $tpd_id,
				'reauth' => 'yes',
			)
		);

		if ( in_array( $tpd_id, $reauthorized, true ) ) {
			$is_suspended = false;
		}
	}

	if ( 'dropbox' === $tpd_type ) {
		if (
			! in_array( $tpd_id, $dropbox_reauthorized, true ) &&
			! in_array( $tpd_id, $dropbox_unauthorized, true )
		) {
			array_push( $dropbox_unauthorized, $tpd_id );
		}

		$dropbox_oauth_link = Dropbox::create_oauth_link(
			array(
				'tpd_id' => $tpd_id,
				'reauth' => 'yes',
			)
		);

		if ( in_array( $tpd_id, $dropbox_reauthorized, true ) ) {
			$is_suspended = false;
		} else {
			$row_class .= ' dropbox--destination__expired';
		}
	}//end if
}//end if

if ( ! in_array( $tpd_id, $reauthorized, true ) && in_array( $tpd_id, $unauthorized, true ) ) {
	$unauthorized = array_diff( $unauthorized, array( $tpd_id ) );
	array_push( $reauthorized, $tpd_id );
}

$onedrive = array();
if ( ! empty( $unauthorized ) || ! empty( $reauthorized ) ) {
	$onedrive['reauthorized'] = $reauthorized;
	$onedrive['unauthorized'] = $unauthorized;
	update_site_option( 'snapshot_onedrive_destination_suspended', $onedrive );
}

$dropbox = array();
if ( ! empty( $dropbox_unauthorized ) || ! empty( $dropbox_reauthorized ) ) {
	$dropbox['reauthorized'] = $dropbox_reauthorized;
	$dropbox['unauthorized'] = $dropbox_unauthorized;
	update_site_option( 'snapshot_dropbox_destination_suspended', $dropbox );
}

// Encrypt the keys:
$tpd_accesskey = snapshot_encrypt_data( $tpd_accesskey );
$tpd_secretkey = snapshot_encrypt_data( $tpd_secretkey );
?>
<tr class="destination-row <?php echo esc_attr( $row_class ); ?> <?php echo ! $aws_storage ? 'deactivated-destination' : ''; ?>"
	data-tpd_id="<?php echo esc_attr( $tpd_id ); ?>" data-tpd_name="<?php echo esc_attr( $tpd_name ); ?>"
	data-tpd_path="<?php echo 'azure' === $tpd_type ? esc_attr( '/' . ( isset( $path_parts[1] ) ? $path_parts[1] : '' ) ) : esc_attr( $tpd_path ); ?>"
	<?php if ( 'ftp' !== $tpd_type && 'sftp' !== $tpd_type ) : ?>
		data-tpd_accesskey="<?php echo esc_attr( $tpd_accesskey ); ?>"
		data-tpd_secretkey="<?php echo esc_attr( $tpd_secretkey ); ?>"
	<?php else: ?>
		data-ftp_username="<?php echo esc_attr( $tpd_accesskey ); ?>"
		data-ftp_password="<?php echo esc_attr( $tpd_secretkey ); ?>"
	<?php endif; ?>

	<?php if ( 'linode' === $tpd_type ) : ?>
		data-ftp_host="<?php echo esc_attr( $meta['ftp_host'] ); ?>"
	<?php endif; ?>

	data-tpd_region="<?php echo esc_attr( $tpd_region ); ?>" data-tpd_limit="<?php echo esc_attr( $tpd_limit ); ?>"
	data-tpd_type="<?php echo esc_attr( $tpd_type ); ?>"
	<?php if ( 'aws' === $tpd_type && isset( $tpd_bucket ) ) : ?>
			data-tpd_bucket="<?php echo esc_attr( $tpd_bucket ); ?>"
	<?php endif; ?>
	<?php if ( 'azure' === $tpd_type && isset( $tpd_bucket ) ) : ?>
			data-tpd_container="<?php echo esc_attr( $tpd_bucket ); ?>"
	<?php endif; ?>
	data-tpd_email="<?php echo isset( $email ) ? esc_attr( $email ) : ''; ?>"
	<?php if ( 'ftp' === $tpd_type || 'sftp' === $tpd_type ) : ?>
		data-ftp-passive-mode="<?php echo ( isset( $meta['ftp_mode'] ) && '1' === $meta['ftp_mode'] ) ? 1 : 0; ?>"
		data-ftp-timeout="<?php echo ( isset( $meta['ftp_timeout'] ) ) ? esc_attr( $meta['ftp_timeout'] ) : 90; ?>"
		data-ftp-port="<?php echo esc_attr( ( isset( $meta['ftp_port'] ) ) ? $meta['ftp_port'] : ( 'ftp' === $tpd_type ? 21 : 22 ) ); ?>"
	<?php endif; ?>
	<?php if ( 'onedrive' === $tpd_type ) : ?>
		data-tpd_drive_id="<?php echo esc_attr( $tpd_drive_id ); ?>"
		data-tpd_item_id="<?php echo esc_attr( $tpd_item_id ); ?>"
	<?php endif; ?>
	<?php if ( in_array( $tpd_type, array( 's3_other', 'linode' ), true ) && ! empty( $endpoint ) ) : ?>
		data-tpd_endpoint="<?php echo esc_url_raw( $endpoint ); ?>"
	<?php endif; ?>
	>
	<td class="sui-table-item-title sui-hidden-xs sui-hidden-sm row-icon <?php echo esc_attr( $icon_class ); ?>">
		<div style="display: flex;">
			<div class="tooltip-container">
				<div class="tooltip-background"></div>
				<div class="tooltip-block <?php echo $tpd_type ? 'sui-tooltip' : ''; ?>"
					data-tooltip="<?php echo esc_attr( $icon_tooltip ); ?>">
				</div>
				<span class="tpd-name"><?php echo esc_html( $tpd_name ); ?></span>
			</div>
			<?php if ( $is_failed_export_gdrive ) : ?>
			<div class="sui-tooltip sui-tooltip-constrained"
				data-tooltip="<?php esc_attr_e( 'It seems Google Drive is not syncing with Snapshot. To make it work again, please reauthenticate.', 'snapshot' ); ?>"
				aria-hidden="true">
				<span class="sui-icon-warning-alert color-yellow"></span>
			</div>
			<?php endif; ?>

			<?php if ( 'onedrive' === $tpd_type && $is_suspended ) : ?>
			<div class="sui-tooltip sui-tooltip-constrained"
				data-tooltip="<?php esc_attr_e( 'It seems OneDrive has stopped syncing with Snapshot. To make it work again, please reauthenticate.', 'snapshot' ); ?>"
				aria-hidden="true">
				<span class="sui-icon-warning-alert color-yellow"></span>
			</div>
			<?php endif; ?>

			<?php if ( 'dropbox' === $tpd_type && $is_suspended ) : ?>
			<div class="sui-tooltip sui-tooltip-constrained"
				data-tooltip="<?php esc_attr_e( 'It seems Dropbox has stopped syncing with Snapshot. To make it work again, please reauthenticate.', 'snapshot' ); ?>"
				aria-hidden="true">
				<span class="sui-icon-warning-alert color-yellow"></span>
			</div>
			<?php endif; ?>
		</div>

	</td>

	<td class="sui-hidden-xs sui-hidden-sm snapshot-destination-path"><span class="sui-icon-folder sui-md"
			aria-hidden="true"></span><span><?php echo esc_html( $tpd_path ); ?></span></td>
	<td class="sui-hidden-xs sui-hidden-sm"><span class="sui-icon-loader sui-loading snapshot-loading-schedule"
			aria-hidden="true"></span><span class="destination-schedule-text"></span></td>
	<td class="sui-hidden-xs sui-hidden-sm backup-count">0</td>

	<td colspan="5" class="sui-table-item-title first-child sui-hidden-md sui-hidden-lg mobile-row">
		<div class="destination-name"><i
				class="destination-icon destination-icon-<?php echo esc_attr( $tpd_type ); ?> <?php echo $tpd_type ? 'sui-tooltip sui-tooltip-right' : ''; ?>"
				data-tooltip="<?php echo esc_attr( $icon_tooltip ); ?>"></i><?php echo esc_html( $tpd_name ); ?></div>
		<div class="sui-row destination-cells">
			<div class="sui-col-xs-6">
				<div class="sui-table-item-title"><?php esc_html_e( 'Directory', 'snapshot' ); ?></div>
				<div class="sui-table-item-title destination-path"><span class="sui-icon-folder sui-md"
						aria-hidden="true"></span><span><?php echo esc_html( $tpd_path ); ?></span></div>
			</div>

			<div class="sui-col-xs-6">
				<div class="sui-table-item-title"><?php esc_html_e( 'Schedule', 'snapshot' ); ?></div>
				<div class="sui-table-item-title"><span class="sui-icon-loader sui-loading snapshot-loading-schedule"
						aria-hidden="true"></span><span class="destination-schedule-text"></span></div>
			</div>

			<div class="sui-col-xs-6">
				<div class="sui-table-item-title"><?php esc_html_e( 'Exported Backups', 'snapshot' ); ?></div>
				<div class="sui-table-item-title backup-count">0</div>
			</div>
		</div>
	</td>

	<td class="destination-actions-cell">
		<div class="destination-actions <?php echo $is_failed_export_gdrive || $is_suspended ? 'reconnect-button' : ''; ?>">
			<?php if ( $is_failed_export_gdrive ) : ?>
				<a href="<?php echo esc_url( $gdrive_oauth_link ); ?>" class="sui-button">
					<?php esc_html_e( 'Reconnect', 'snapshot' ); ?>
				</a>

			<?php elseif ( $is_suspended && 'onedrive' === $tpd_type ) : ?>
				<?php if ( '' !== $onedrive_oauth_link ) : ?>
					<a href="<?php echo esc_url( $onedrive_oauth_link ); ?>" class="sui-button">
						<?php esc_html_e( 'Reconnect', 'snapshot' ); ?>
					</a>
				<?php endif; ?>
			<?php elseif ( $is_suspended && 'dropbox' === $tpd_type ) : ?>
				<?php if ( '' !== $dropbox_oauth_link ) : ?>
					<a href="<?php echo esc_url( $dropbox_oauth_link ); ?>" class="sui-button">
						<?php esc_html_e( 'Reconnect', 'snapshot' ); ?>
					</a>
				<?php endif; ?>
			<?php else : ?>
				<div class="sui-form-field">
					<label class="sui-toggle sui-tooltip"
						data-tooltip="<?php $aws_storage ? esc_attr_e( 'Deactivate destination', 'snapshot' ) : esc_attr_e( 'Activate destination', 'snapshot' ); ?>">
						<input type="checkbox" class="toggle-active" <?php echo $aws_storage ? 'checked' : ''; ?>>
						<span class="sui-toggle-slider" aria-hidden="true"></span>
					</label>
				</div>
			<?php endif; ?>
			<div class="sui-dropdown sui-tooltip" data-tooltip="<?php esc_attr_e( 'Settings', 'snapshot' ); ?>">
				<button class="sui-button-icon sui-dropdown-anchor">
					<span class="sui-icon-widget-settings-config" aria-hidden="true"></span>
					<span
						class="sui-screen-reader-text"><?php esc_html_e( 'Destination actions', 'snapshot' ); ?></span>
				</button>
				<ul>
					<li class="destination-edit">
						<button><span class="sui-icon-pencil" aria-hidden="true"></span>
							<?php esc_html_e( 'Edit destination', 'snapshot' ); ?></button>
					</li>
					<?php if ( 'ftp' !== $tpd_type && 'sftp' !== $tpd_type && 'azure' !== $tpd_type && 'linode' !== $tpd_type ) : ?>
						<li class="destination-view">
							<a href="<?php echo esc_url( $view_url ); ?>"
								<?php
								if ( '#' !== $view_url && '' !== $view_url ) {
									echo 'target="_blank" rel="noopener noreferrer"';
								}
								?>
								>
								<span class="sui-icon-link" aria-hidden="true"></span>
								<?php esc_html_e( 'View Directory', 'snapshot' ); ?>
							</a>
						</li>
					<?php endif; ?>
					<li class="destination-delete">
						<button class="sui-option-red">
							<span class="sui-icon-trash" aria-hidden="true"></span>
							<?php esc_html_e( 'Delete', 'snapshot' ); ?>
						</button>
					</li>
				</ul>
			</div>
		</div>
	</td>
</tr>