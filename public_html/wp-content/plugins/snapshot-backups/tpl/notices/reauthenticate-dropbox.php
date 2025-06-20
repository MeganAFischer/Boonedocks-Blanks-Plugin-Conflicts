<?php
/**
 * Notice tempalte file to re-authenticate Dropbox
 *
 * @package Snapshot_Backups
 * @since 4.33.0
 */

$dropbox_lists = get_site_option( 'snapshot_dropbox_destination_suspended', array() );
$notice_status = get_site_option( 'snapshot_reconnect_dropbox_notice_status' );

if ( empty( $dropbox_lists ) ) {
	return;
}

if ( ! isset( $dropbox_lists['unauthorized'] ) || empty( $dropbox_lists['unauthorized'] ) ) {
	return;
}

if ( $notice_status ) {
	return;
}

$assets            = new \WPMUDEV\Snapshot4\Helper\Assets();
$destinations_page = network_admin_url() . 'admin.php?page=snapshot-destinations';

wp_enqueue_style( 'snapshot-onedrive-notice' );

global $pagenow;
$class = 'snapshot-notice--box snapshot-connect-dropbox--notice';
if ( 'index.php' === $pagenow ) {
	$class .= ' snapshot-admin--dashboard notice';
}
?>

<div class="<?php echo esc_attr( $class ); ?>" style="border-left: 3px solid #17A8E3;">
	<div class="snapshot-box--image__space sui-box-image-space" aria-hidden="true">
		<img
		src="<?php echo esc_url( $assets->get_asset( 'img/dropbox-notice-icon.png' ) ); ?>"
		srcset="<?php echo esc_url( $assets->get_asset( 'img/dropbox-notice-icon.png' ) ); ?> 1x, <?php echo esc_url( $assets->get_asset( 'img/dropbox-notice-icon@2x.png' ) ); ?> 2x"
		alt="<?php esc_attr_e( 'Dropbox icon', 'snapshot' ); ?>"
		>
	</div>

	<div class="snapshot-summary--segment">
		<div class="snapshot-summary--details">
			<h3 class="no-spacing snapshot-no--spacing"><?php esc_html_e( 'Reconnect Dropbox', 'snapshot' ); ?></h3>
			<div class="snapshot-block-text"><?php esc_html_e( 'It appears that Dropbox is not syncing with Snapshot. Please reauthenticate your Dropbox to ensure it works properly.', 'snapshot' ); ?></div>
			<a href="<?php echo esc_url( $destinations_page ); ?>" class="snapshot-button snapshot-button--blue"><span class="xsui-icon-link" aria-hidden="true"></span> <?php esc_html_e( 'Reconnect Now', 'snapshot' ); ?></a>
			<a class="snapshot-button snapshot-button--light__gray do-not--nag__me--dropbox" data-nonce="<?php echo wp_create_nonce( 'snapshot_gdrive_reconnect_nag' ); ?>"><?php esc_html_e( 'Don\'t remind me again', 'snapshot' ); ?></a>
		</div>
	</div>
</div>



<?php if ( 'index.php' === $pagenow ) : ?>
	<script type="text/javascript">
		const notice = document.querySelector( '.snapshot-connect-dropbox--notice' );
		const doNotNagMe = document.querySelector( '.do-not--nag__me--dropbox' );

		if ( doNotNagMe ) {
			doNotNagMe.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				const nonce = e.target.dataset.nonce;

				fetch( `${ ajaxurl }?action=snapshot-remove_destination_nag&_wpnonce=${ nonce }&type=dropbox` )
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