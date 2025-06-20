<?php
/**
 * Template: Settings Import Notice.
 *
 * @package Smartcrwal
 */

namespace SmartCrawl;

$settings_errors = \SmartCrawl\Third_Party_Import\Controller::get()->get_errors();
?>
<div class="sui-floating-notices">
	<?php
	if ( 'success' === \smartcrawl_get_array_value( $_GET, 'import' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$this->render_view(
			'floating-notice',
			array(
				'code'      => 'wds-crawl-started',
				'type'      => 'success',
				'message'   => esc_html__( 'Settings successfully imported', 'wds' ),
				'autoclose' => true,
			)
		);
	} elseif ( ! empty( $settings_errors ) ) {
		$this->render_view(
			'floating-notice',
			array(
				'code'      => 'wds-import-error',
				'type'      => 'error',
				'message'   => array_shift( $settings_errors ),
				'autoclose' => false,
			)
		);
	}
	?>
</div>