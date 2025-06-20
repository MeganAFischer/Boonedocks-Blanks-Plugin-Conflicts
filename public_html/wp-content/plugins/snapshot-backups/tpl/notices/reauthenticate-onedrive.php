<?php
/**
 * Notice tempalte file to re-authenticate google drive
 *
 * @package Snapshot_Backups
 * @since 4.24.0
 */

$onedrive_lists = get_site_option( 'snapshot_onedrive_destination_suspended', array() );
$notice_status  = get_site_option( 'snapshot_reconnect_onedrive_notice_status' );

if ( empty( $onedrive_lists ) ) {
	return;
}

if ( ! isset( $onedrive_lists['unauthorized'] ) || empty( $onedrive_lists['unauthorized'] ) ) {
	return;
}

if ( $notice_status ) {
	return;
}

$assets            = new \WPMUDEV\Snapshot4\Helper\Assets();
$destinations_page = network_admin_url() . 'admin.php?page=snapshot-destinations';

wp_enqueue_style( 'snapshot-onedrive-notice' );

global $pagenow;
$class = 'snapshot-notice--box snapshot-connect-onedrive--notice';
if ( 'index.php' === $pagenow ) {
	$class .= ' snapshot-admin--dashboard notice';
}
?>

<div class="<?php echo esc_attr( $class ); ?>" style="border-left: 3px solid #17A8E3;">
	<div class="snapshot-box--image__space sui-box-image-space" aria-hidden="true">
		<img
		src="<?php echo esc_url( $assets->get_asset( 'img/onedrive-notice-icon.png' ) ); ?>"
		srcset="<?php echo esc_url( $assets->get_asset( 'img/onedrive-notice-icon.png' ) ); ?> 1x, <?php echo esc_url( $assets->get_asset( 'img/onedrive-notice-icon@2x.png' ) ); ?> 2x"
		alt="<?php esc_attr_e( 'Onedrive icon', 'snapshot' ); ?>"
		>
	</div>

	<div class="snapshot-summary--segment">
		<div class="snapshot-summary--details">
			<h3 class="no-spacing snapshot-no--spacing"><?php esc_html_e( 'Reconnect OneDrive', 'snapshot' ); ?></h3>
			<div class="snapshot-block-text"><?php esc_html_e( 'It appears that OneDrive is not syncing with Snapshot. Please reauthenticate your OneDrive to ensure it works properly.', 'snapshot' ); ?></div>
			<a href="<?php echo esc_url( $destinations_page ); ?>" class="snapshot-button snapshot-button--blue"><span class="xsui-icon-link" aria-hidden="true"></span> <?php esc_html_e( 'Reconnect Now', 'snapshot' ); ?></a>
			<a class="snapshot-button snapshot-button--light__gray do-not--nag__me--onedrive" data-nonce="<?php echo wp_create_nonce( 'snapshot_gdrive_reconnect_nag' ); ?>"><?php esc_html_e( 'Don\'t remind me again', 'snapshot' ); ?></a>
		</div>
	</div>
</div>



<?php if ( 'index.php' === $pagenow ) : ?>
	<script type="text/javascript">
		const notice = document.querySelector( '.snapshot-connect-onedrive--notice' );
		const doNotNagMe = document.querySelector( '.do-not--nag__me--onedrive' );

		if ( doNotNagMe ) {
			doNotNagMe.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				const nonce = e.target.dataset.nonce;

				fetch( `${ ajaxurl }?action=snapshot-remove_destination_nag&_wpnonce=${ nonce }&type=onedrive` )
					.then( ( response ) => response.json() )
					.then( ( data ) => {
						if ( data.success ) {
							notice.style.display = 'none';
						}
					} )
					.catch( ( err ) => {
						console.error( err );
					} );
			})
		}
	</script>
<?php endif; ?>