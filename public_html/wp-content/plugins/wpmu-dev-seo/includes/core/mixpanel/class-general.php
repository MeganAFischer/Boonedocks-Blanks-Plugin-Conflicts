<?php
/**
 * Class to handle mixpanel general events functionality.
 *
 * @since   3.7.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Mixpanel;

use SmartCrawl\Singleton;
use SmartCrawl\Settings;

/**
 * Mixpanel General Events class
 */
class General extends Events {

	use Singleton;

	/**
	 * Initialize class.
	 *
	 * @since 3.7.0
	 */
	protected function init() {
		add_action( 'smartcrawl_after_reset_settings', array( $this, 'intercept_settings_reset' ) );
		add_action( 'smartcrawl_after_uninstall', array( $this, 'intercept_plugin_uninstall' ), 10, 2 );
		add_action( 'deactivated_plugin', array( $this, 'intercept_deactivate' ) );
		add_action( 'update_option_wds_settings_options', array( $this, 'intercept_settings_update' ), 10, 2 );
		add_action( 'smartcrawl_after_onboarding_skip', array( $this, 'intercept_onboarding_skip' ) );
		add_action( 'smartcrawl_after_onboarding_done', array( $this, 'intercept_onboarding_done' ) );
	}

	/**
	 * Handle onboarding skip event.
	 *
	 * @since 3.7.0
	 *
	 * @return void
	 */
	public function intercept_onboarding_skip() {
		if ( ! $this->is_tracking_active() ) {
			return;
		}

		// Send plugin deactivation event.
		$this->tracker()->track(
			'SMA - Quick Setup',
			array(
				'module'        => '',
				'advanced_tool' => '',
				'action'        => 'Skip',
			)
		);
	}

	/**
	 * Handle onboarding done event.
	 *
	 * @since 3.7.0
	 *
	 * @return void
	 */
	public function intercept_onboarding_done() {
		if ( ! $this->is_tracking_active() ) {
			return;
		}

		$settings = Settings::get_specific_options( 'wds_settings_options' );
		$social   = Settings::get_component_options( Settings::COMP_SOCIAL );
		$modules  = array(
			'analysis-seo'        => 'SEO and Readability Analysis',
			'sitemap'             => 'Sitemaps',
			'twitter-card-enable' => 'OpenGraph & Twitter Cards',
			'usage_tracking'      => 'Usage Tracking Opt-In',
		);

		$active_modules = array();

		// Get active modules.
		foreach ( $modules as $module => $label ) {
			if ( ! empty( $settings[ $module ] ) || ! empty( $social[ $module ] ) ) {
				$active_modules[] = $label;
			}
		}

		// Send plugin deactivation event.
		$this->tracker()->track(
			'SMA - Quick Setup',
			array(
				'module'        => wp_json_encode( $active_modules ),
				'advanced_tool' => ! empty( $settings['robots-txt'] ) ? 'Robots.txt File Editor' : '',
				'action'        => 'Get Started',
			)
		);
	}

	/**
	 * Handle settings reset.
	 *
	 * We need to opt out after settings reset.
	 *
	 * @since 3.7.0
	 *
	 * @param array $old_options Old options data before reset.
	 *
	 * @return void
	 */
	public function intercept_settings_reset( $old_options ) {
		$usage = $this->get_value( 'usage_tracking', $old_options );
		// Opt out only if it was opted in before reset.
		if ( $usage ) {
			$this->track_opt_toggle( false );
		}
	}

	/**
	 * Handle settings uninstall.
	 *
	 * We need to opt out after plugin uninstall if not keep settings.
	 *
	 * @param array $options Old options data before reset.
	 * @param bool  $keep_settings Determine whether to save current settings for next time, or reset them.
	 *
	 * @return void
	 *
	 * @since 3.7.0
	 */
	public function intercept_plugin_uninstall( $options, $keep_settings ) {
		$usage = $this->get_value( 'usage_tracking', $options );

		// Opt out only if it was opted in before and doesn't require to keep settings.
		if ( $usage && ! $keep_settings ) {
			$this->track_opt_toggle( false );
		}
	}

	/**
	 * Handle plugin deactivation.
	 *
	 * @since 3.7.0
	 *
	 * @param string $plugin Path to the plugin file relative to the plugins directory.
	 *
	 * @return void
	 */
	public function intercept_deactivate( $plugin ) {
		if ( ! $this->is_tracking_active() ) {
			return;
		}

		// Only if SmartCrawl.
		if ( SMARTCRAWL_PLUGIN_BASENAME !== $plugin ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';

		$triggered_from = 'Unknown';

		// Deactivated from WPMUDEV Dashboard.
		if ( 'wdp-project-deactivate' === $action ) {
			$triggered_from = 'WPMU DEV Dashboard Plugins Page';
		} elseif ( 'deactivate' === $action ) {
			// Deactivated from WP plugins page.
			$triggered_from = 'WordPress Plugins Page';
		}

		// Send plugin deactivation event.
		$this->tracker()->track(
			'SMA - Plugin Deactivated',
			array(
				'competitor_plugins' => $this->get_competitors(),
				'triggered_from'     => $triggered_from,
			)
		);
	}

	/**
	 * Handle settings update.
	 *
	 * If data tracking is disabled, make sure to trigger opt in or opt out.
	 *
	 * @param mixed $old_value The old option values.
	 * @param mixed $new_value The new option values.
	 *
	 * @return void
	 *
	 * @since 3.7.0
	 */
	public function intercept_settings_update( $old_value, $new_value ) {
		if ( isset( $new_value['usage_tracking'], $old_value['usage_tracking'] ) && $new_value['usage_tracking'] !== $old_value['usage_tracking'] ) {
			$this->track_opt_toggle( ! empty( $new_value['usage_tracking'] ) );
		}

		if ( ! $this->is_tracking_active() ) {
			return;
		}
	}

	/**
	 * Track data tracking opt in and opt out.
	 *
	 * @since 3.7.0
	 *
	 * @param bool $active Toggle value.
	 *
	 * @return void
	 */
	private function track_opt_toggle( $active ) {
		$this->tracker()->track( $active ? 'Opt In' : 'Opt Out' );
	}

	/**
	 * Get competitor plugins.
	 *
	 * @since 3.7.0
	 *
	 * @return string
	 */
	private function get_competitors() {
		$competitors = array();

		$plugins = array(
			'wordpress-seo/wp-seo.php'                    => 'Yoast SEO',
			'wordpress-seo-premium/wp-seo-premium.php'    => 'Yoast SEO Premium',
			'seo-by-rank-math/rank-math.php'              => 'Rank Math',
			'seo-by-rank-math-pro/rank-math-pro.php'      => 'Rank Math SEO PRO',
			'all-in-one-seo-pack/all_in_one_seo_pack.php' => 'All In One SEO',
			'all-in-one-seo-pack-pro/all_in_one_seo_pack.php' => 'All in One SEO Pro',
			'wp-seopress/seopress.php'                    => 'SEOPress',
			'wp-seopress-pro/seopress-pro.php'            => 'SEOPress PRO',
			'premium-seo-pack/index.php'                  => 'Premium SEO Pack',
		);

		foreach ( $plugins as $plugin => $name ) {
			if ( is_plugin_active( $plugin ) || is_plugin_active_for_network( $plugin ) ) {
				$competitors[] = $name;
			}
		}

		return implode( ', ', $competitors );
	}
}