<?php
/**
 * Template: Sitemap Notices.
 *
 * @package Smartcrwal
 */

?>

<div class="sui-floating-notices">
	<?php
	if ( ! empty( $_GET['crawl-in-progress'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$this->render_view(
			'floating-notice',
			array(
				'code'      => 'wds-crawl-started',
				'type'      => 'success',
				'message'   => esc_html__( 'Crawl started successfully', 'wds' ),
				'autoclose' => true,
			)
		);
	}
	if ( ! empty( $_GET['switched-to-native'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$this->render_view(
			'floating-notice',
			array(
				'code'      => 'wds-switched-to-native',
				'type'      => 'success',
				'message'   => \smartcrawl_format_link(
				/* translators: %s: Link to wp-sitemap.xml */
					esc_html__( 'You have successfully switched to the Wordpress core sitemap. You can find it at %s', 'wds' ),
					home_url( '/wp-sitemap.xml' ),
					'/wp-sitemap.xml',
					'_blank'
				),
				'autoclose' => true,
			)
		);
	}
	if ( ! empty( $_GET['switched-to-sc'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$this->render_view(
			'floating-notice',
			array(
				'code'      => 'wds-switched-to-sc',
				'type'      => 'success',
				'message'   => sprintf(
					/* translators: 1,2: strong tag, 3: plugin title, 4: link to sitemap */
					esc_html__( 'Well done! You have successfully switched to the %1$s%3$s%2$s sitemap. You can find it at %4$s', 'wds' ),
					'<strong>',
					'</strong>',
					\smartcrawl_get_plugin_title(),
					\smartcrawl_format_link(
						'%s',
						\smartcrawl_get_sitemap_url(),
						'/sitemap.xml',
						'_blank'
					)
				),
				'autoclose' => true,
			)
		);
	}
	?>
</div>