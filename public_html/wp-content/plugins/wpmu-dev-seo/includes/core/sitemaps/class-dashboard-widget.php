<?php
/**
 * Class Dashboard_Widget
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Sitemaps;

use SmartCrawl\Settings;
use SmartCrawl\Simple_Renderer;
use SmartCrawl\Singleton;
use SmartCrawl\Controllers;
use SmartCrawl\Admin\Settings\Admin_Settings;

/**
 * Init WDS Sitemaps Dashboard Widget
 *
 * TODO: move the information in this widget to the SC dashboard widget and get rid of this
 */
class Dashboard_Widget extends Controllers\Controller {

	use Singleton;

	/**
	 * Should this module run?.
	 *
	 * @return bool
	 */
	public function should_run() {
		return (
			Settings::get_setting( 'sitemap' )
			&& Admin_Settings::is_tab_allowed( Settings::TAB_SITEMAP )
		);
	}

	/**
	 * Initialize the module.
	 *
	 * @return void
	 */
	protected function init() {
		add_action( 'wp_dashboard_setup', array( &$this, 'dashboard_widget' ) );
	}

	/**
	 * Dashboard Widget.
	 *
	 * @return void
	 */
	public function dashboard_widget() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'wds_sitemaps_dashboard_widget',
			sprintf(
				/* translators: %s: plugin title */
				__( 'Sitemaps - %s', 'wds' ),
				\smartcrawl_get_plugin_title()
			),
			array(
				&$this,
				'widget',
			)
		);
	}

	/**
	 * Widget content.
	 */
	public function widget() {
		$sitemap_options  = Settings::get_options();
		$sitemap_stats    = get_option( 'wds_sitemap_dashboard' );
		$engines          = get_option( 'wds_engine_notification' );
		$last_update_date = ! empty( $sitemap_stats['time'] ) ? date_i18n( get_option( 'date_format' ), $sitemap_stats['time'] ) : false;
		$last_update_time = ! empty( $sitemap_stats['time'] ) ? date_i18n( get_option( 'time_format' ), $sitemap_stats['time'] ) : false;
		// translators: last updated date & last updated time.
		$last_update_timestamp    = ( $last_update_date && $last_update_time ) ? sprintf( esc_html__( 'It was last updated on %1$s, at %2$s.', 'wds' ), $last_update_date, $last_update_time ) : esc_html__( "Your sitemap hasn't been updated recently.", 'wds' );
		$se_notifications_enabled = (bool) \smartcrawl_get_array_value( $sitemap_options, 'ping-google' ) || (bool) \smartcrawl_get_array_value( $sitemap_options, 'ping-bing' );

		Simple_Renderer::render(
			'wp-dashboard/sitemaps-widget',
			array(
				'engines'                  => $engines,
				'sitemap_stats'            => $sitemap_stats,
				'last_update_date'         => $last_update_date,
				'last_update_time'         => $last_update_time,
				'last_update_timestamp'    => $last_update_timestamp,
				'se_notifications_enabled' => $se_notifications_enabled,
			)
		);

		Simple_Renderer::render(
			'wp-dashboard/sitemaps-widget-js',
			array(
				'updating'  => __( 'Updating...', 'wds' ),
				'updated'   => __( 'Done updating the sitemap, please hold on...', 'wds' ),
				'notifying' => __( 'Notifying...', 'wds' ),
				'notified'  => __( 'Done notifying search engines, please hold on...', 'wds' ),
			)
		);
	}
}