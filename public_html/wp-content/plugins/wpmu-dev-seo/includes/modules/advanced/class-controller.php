<?php
/**
 * Controller for Advanced module.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Modules\Advanced;

use SmartCrawl\Admin\Module_Settings;
use SmartCrawl\Controllers;
use SmartCrawl\Settings;
use SmartCrawl\Singleton;

/**
 * Redirects Controller.
 */
class Controller extends Controllers\Module_Controller {

	use Singleton;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		$this->module_name  = Settings::ADVANCED_MODULE;
		$this->module_title = __( 'Advanced Tools', 'wds' );
		$this->position     = 6;

		$this->submodules = array(
			Settings::AUTOLINKS_SUBMODULE   => Autolinks\Controller::get(),
			Settings::REDIRECTS_SUBMODULE   => Redirects\Controller::get(),
			Settings::WOOCOMMERCE_SUBMODULE => WooCommerce\Controller::get(),
			Settings::SEOMOZ_SUBMODULE      => Seomoz\Controller::get(),
			Settings::ROBOTS_SUBMODULE      => Robots\Controller::get(),
			Settings::BREADCRUMB_SUBMODULE  => Breadcrumbs\Controller::get(),
		);

		parent::__construct();
	}

	/**
	 * Should this module run?
	 *
	 * @return bool
	 */
	public function should_run() {
		return $this->is_active();
	}

	/**
	 * Includes methods when the controller stops running.
	 *
	 * @return void
	 */
	protected function terminate() {}

	/**
	 * Sanitizes submitted options
	 *
	 * @param array $input Raw input.
	 *
	 * @return bool True if sanitized successfully, otherwise false.
	 */
	public function sanitize_options( $input ) {
		return true;
	}

	/**
	 * Updates module option and saves to db.
	 *
	 * @param string $option Name of the option to update.
	 * @param mixed  $value Option value.
	 *
	 * @return void
	 */
	public function update_option( $option = '', $value = false ) {
		if ( $option ) {
			$this->options[ $option ] = $value;
		} else {
			$this->options = array_merge( $this->options, $value );
		}

		update_option( $this->module_name, $this->options );

		$this->run();
	}

	/**
	 * Outputs the content for this module's page.
	 */
	public function output_page() {

		$submodules = array();

		foreach ( $this->submodules as $submodule_name => $handler ) {
			if ( $handler->is_active() ) {
				$submodule = array(
					'id'    => $submodule_name,
					'title' => $handler->module_title,
				);

				if ( Settings::REDIRECTS_SUBMODULE === $submodule_name && (int) Settings::get_specific_options( 'wds-features-viewed', 0 ) < 2 ) {
					$submodule['new_feature'] = 1;
				}

				$submodules[] = $submodule;
			}
		}

		wp_localize_script(
			$this->module_name,
			"_wds_{$this->module_id}",
			array(
				'submodules' => $submodules,
			)
		);

		parent::output_page();
	}
}