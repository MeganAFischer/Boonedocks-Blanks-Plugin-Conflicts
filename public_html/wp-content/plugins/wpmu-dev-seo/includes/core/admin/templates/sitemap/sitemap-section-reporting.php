<?php
/**
 * Template: Sitemap Reporting section.
 *
 * @package Smartcrwal
 */

namespace SmartCrawl;

$cron        = \SmartCrawl\Controllers\Cron::get();
$option_name = $_view['option_name'];

// This does the actual rescheduling.
$cron->set_up_schedule();
$is_member            = ! empty( $_view['is_member'] );
$crawler_cron_enabled = ! empty( $_view['options']['crawler-cron-enable'] ) && $is_member;
$toggle_field_name    = $option_name . '[crawler-cron-enable]';
$dash_profile_data    = \smartcrawl_get_dash_profile_data();
$crawler_freq         = empty( $_view['options']['crawler-frequency'] ) ? false : $_view['options']['crawler-frequency'];
$frequencies          = $cron->get_frequencies();
$email_recipients     = empty( $email_recipients ) ? array() : $email_recipients;
?>

<div class="wds-upsell-tab-description">
	<div>
		<p>
			<?php
			printf(
				/* translators: 1,2: strong tag, 3: plugin title */
				esc_html__( 'Set up %1$s%3$s%2$s to automatically crawl your URLs daily, weekly or monthly and send an email report to your inbox.', 'wds' ),
				'<strong>',
				'</strong>',
				esc_html( \smartcrawl_get_plugin_title() )
			);
			?>
			</p>
	</div>

	<?php if ( $crawler_cron_enabled && $dash_profile_data && $crawler_freq ) : ?>
		<?php
		$this->render_view(
			'notice',
			array(
				'message' => sprintf(
					'Automatic crawls are enabled and sending %s to %d recipient.',
					\smartcrawl_get_array_value( $frequencies, $crawler_freq ),
					count( $email_recipients )
				),
				'class'   => 'sui-notice-info',
			)
		);
		?>
	<?php endif; ?>
</div>
<div class="sui-box-settings-row <?php echo $is_member ? '' : 'sui-disabled'; ?>">
	<div class="sui-box-settings-col-1">
		<label class="sui-settings-label">

			<?php esc_html_e( 'Schedule automatic crawls', 'wds' ); ?>
		</label>

		<span class="sui-description">
			<?php esc_html_e( 'Enable automated sitemap crawl reports for this website.', 'wds' ); ?>
		</span>
	</div>
	<div class="sui-box-settings-col-2">
		<?php
		$this->render_view(
			'toggle-item',
			array(
				'field_name'                 => $toggle_field_name,
				'field_id'                   => $toggle_field_name,
				'checked'                    => $crawler_cron_enabled,
				'item_label'                 => esc_html__( 'Run regular URL crawls', 'wds' ),
				'sub_settings_template'      => 'sitemap/sitemap-reporting-toggle-sub-settings',
				'sub_settings_template_args' => array(
					'email_recipients' => $email_recipients,
				),
			)
		);
		?>
	</div>

</div>

<?php if ( ! $is_member ) : ?>
	<?php
	$this->render_view(
		'mascot-message',
		array(
			'key'         => 'seo-checkup-upsell',
			'dismissible' => false,
			'message'     => sprintf(
				'%s <a target="_blank" class="sui-button sui-button-purple" href="https://wpmudev.com/project/smartcrawl-wordpress-seo/?utm_source=smartcrawl&utm_medium=plugin&utm_campaign=smartcrawl_sitemap_reporting_upsell_notice">%s</a>',
				esc_html__( 'Unlock automated crawls of your URLs to always stay on top of any issues with SmartCrawl Pro. Get Sitemap Reports as part of a WPMU DEV membership along with other pro plugins and services, 24/7 support and much more', 'wds' ),
				esc_html__( 'Unlock now with Pro', 'wds' )
			),
		)
	);
	?>
<?php endif; ?>