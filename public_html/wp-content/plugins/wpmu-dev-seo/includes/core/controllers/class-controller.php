<?php
/**
 * Abstract class to control module & submodules.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Controllers;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Controller Abstract.
 */
abstract class Controller {

	/**
	 * Currently running state flag.
	 *
	 * @var bool
	 */
	private $is_running = false;

	/**
	 * Controller data options.
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * Constructor.
	 */
	protected function __construct() {}

	/**
	 * Do the thing!
	 */
	public function run() {
		if ( ! $this->is_active() ) {
			return false;
		}

		if ( $this->is_running() ) {
			return false;
		}

		$this->is_running = true;

		// Some parts need to be run every time.
		$this->always();

		if ( $this->should_run() ) {
			// while the rest are run when should_run returns true.
			$this->init();

			return true;
		}

		return false;
	}

	/**
	 * Should include methods which runs always.
	 *
	 * @return void
	 */
	protected function always() {}

	/**
	 * Child controllers can use this method to initialize.
	 *
	 * @return void
	 */
	abstract protected function init();

	/**
	 * Terminates running controller.
	 *
	 * @return void
	 */
	public function stop() {
		if ( ! $this->is_running() ) {
			return;
		}

		$this->is_running = false;

		$this->terminate();
	}

	/**
	 * Opposite of init.
	 *
	 * @return void
	 */
	protected function terminate() {
	}

	/**
	 * Checks if current module is active.
	 *
	 * @return bool
	 */
	public function is_active() {
		return true;
	}

	/**
	 * Checks if we already have the actions bound.
	 *
	 * @return bool Status
	 */
	public function is_running() {
		return $this->is_running;
	}

	/**
	 * Whether or not this controller should run in the current context.
	 * Default is true which means it will always run.
	 *
	 * @return bool
	 */
	public function should_run() {
		return true;
	}

	/**
	 * Returns controller data options.
	 *
	 * @return array
	 */
	public function get_options() {
		return $this->options;
	}

	/**
	 * Sets controller data options.
	 *
	 * @param array $options Options to be set.
	 *
	 * @return void
	 */
	public function set_options( $options = array() ) {
		$this->options = $options;
	}
}