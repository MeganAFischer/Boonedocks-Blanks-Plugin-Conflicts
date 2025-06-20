<?php
/**
 * Class to handle mixpanel module activation and deactivation functionality.
 *
 * @since   3.7.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Mixpanel;

use SmartCrawl\Singleton;
use SmartCrawl\Settings;

/**
 * Mixpanel Modules Events class
 */
class Modules extends Events {

	use Singleton;

	/**
	 * Tracked modules.
	 *
	 * @var array
	 */
	private $tracked = array();

	/**
	 * Initialize class.
	 *
	 * @since 3.7.0
	 */
	protected function init() {
		$this->tracked = array();

		add_action( 'update_option_wds_settings_options', array( $this, 'intercept_settings_update' ), 10, 3 );
		add_action( 'smartcrawl_after_update_specific_options', array( $this, 'intercept_module_toggle' ), 10, 3 );
	}

	/**
	 * Handle settings update.
	 *
	 * Schema module is saved in general settings.
	 *
	 * @since 3.7.0
	 *
	 * @param mixed  $old_values The old option values.
	 * @param mixed  $new_values The new option values.
	 * @param string $option     Option key.
	 *
	 * @return void
	 */
	public function intercept_settings_update( $old_values, $new_values, $option ) {
		if ( ! $this->is_tracking_active() ) {
			return;
		}

		// Trigger module toggle.
		if ( 'wds_settings_options' === $option ) {
			$this->intercept_module_toggle( $new_values, $old_values, $option );
		}
	}

	/**
	 * Handle modules status change actions.
	 *
	 * @since 3.7.0
	 *
	 * @param mixed  $new_values The new option values.
	 * @param mixed  $old_values The old option values.
	 * @param string $option     Option key.
	 *
	 * @return void
	 */
	public function intercept_module_toggle( $new_values, $old_values, $option ) {
		if ( ! $this->is_tracking_active() ) {
			return;
		}

		// Get from action.
		$action = $this->get_current_action();
		switch ( $action ) {
			case 'smartcrawl_onboard_toggle':
				$from = 'Quick Setup';
				break;
			case 'wds-activate-component':
				$from = 'Dashboard';
				break;
			case 'wds-deactivate-sitemap-module':
				$from = 'Sitemaps';
				break;
			case 'wds-change-schema-status':
				$from = 'Schema';
				break;
			case 'wds_change_social_status':
				$from = 'Social';
				break;
			case 'wds-deactivate-onpage-module':
				$from = 'Title & Meta';
				break;
			default:
				$from = '';
		}

		if ( empty( $from ) ) {
			$from = $this->get_module_from_page();
		}
		if ( empty( $from ) ) {
			$from = $this->get_module_from_activation();
		}

		foreach ( $this->get_modules() as $module => $label ) {
			// Handle schema key.
			$module = Settings::COMP_SCHEMA === $module ? 'disable-schema' : $module;

			// Skip if already tracked.
			if ( in_array( $module, $this->tracked, true ) ) {
				continue;
			}

			$old_status = $this->get_value( $module, $old_values );
			$new_status = $this->get_value( $module, $new_values );

			// If toggle status changed.
			if ( $old_status !== $new_status ) {
				// Handle schema status.
				$new_status = 'disable-schema' === $module ? ! $new_status : $new_status;

				$this->track_module_toggle( $new_status, $label, $from );

				$this->tracked[] = $module;
			}
		}
	}

	/**
	 * Get current action name.
	 *
	 * @since 3.7.0
	 *
	 * @return string
	 */
	private function get_current_action() {
		if ( isset( $_REQUEST['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		return '';
	}

	/**
	 * Get module name from page id.
	 *
	 * @since 3.7.0
	 *
	 * @return string
	 */
	private function get_module_from_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_REQUEST['option_page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['option_page'] ) ) : '';

		$modules = $this->get_modules();
		// Include general settings.
		$modules['settings'] = 'General Settings';

		// Get module name alone.
		$page = preg_replace( '/^wds_/', '', $page );
		$page = preg_replace( '/_options$/', '', $page );

		if ( isset( $modules[ $page ] ) ) {
			return $modules[ $page ];
		}

		return '';
	}

	/**
	 * Get module from the input.
	 *
	 * Usually when activated from the module page.
	 *
	 * @since 3.7.0
	 *
	 * @return string
	 */
	private function get_module_from_activation() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_REQUEST['wds-activate-component'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['wds-activate-component'] ) ) : '';

		$modules = $this->get_modules();

		if ( isset( $modules[ $page ] ) ) {
			return $modules[ $page ];
		}

		return '';
	}

	/**
	 * Get the list of modules with labels.
	 *
	 * @since 3.7.0
	 *
	 * @return array
	 */
	private function get_modules() {
		return array(
			Settings::COMP_SEO         => 'SEO Analysis',
			Settings::COMP_READABILITY => 'Readability Analysis',
			Settings::COMP_SCHEMA      => 'Schema',
			Settings::COMP_ONPAGE      => 'Title & Meta',
			Settings::COMP_SITEMAP     => 'Sitemaps',
			Settings::COMP_SOCIAL      => 'Social',
		);
	}

	/**
	 * Track a module activation and deactivation.
	 *
	 * @since 3.7.0
	 *
	 * @param bool   $active Toggle value.
	 * @param string $module Module label.
	 * @param string $from   Triggered from.
	 *
	 * @return void
	 */
	private function track_module_toggle( $active, $module, $from = '' ) {
		$this->tracker()->track(
			$active ? 'SMA - Module Activated' : 'SMA - Module Deactivated',
			array(
				'module'         => $module,
				'triggered_from' => $from,
			)
		);
	}
}