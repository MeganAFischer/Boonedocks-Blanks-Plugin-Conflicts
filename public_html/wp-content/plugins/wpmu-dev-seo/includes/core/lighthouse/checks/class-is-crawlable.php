<?php
/**
 * Class for checking if the page is crawlable.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Lighthouse\Checks;

use SmartCrawl\Cache\Post_Cache;
use SmartCrawl\Entities\Blog_Home;
use SmartCrawl\Lighthouse\Tables\Table;
use SmartCrawl\Settings;
use SmartCrawl\Simple_Renderer;
use SmartCrawl\Admin\Settings\Admin_Settings;

/**
 * Is_Crawlable class.
 *
 * Checks if the page is crawlable.
 */
class Is_Crawlable extends Check {
	const ID = 'is-crawlable';

	/**
	 * Prepares the check by setting success and failure titles.
	 *
	 * @return void
	 */
	public function prepare() {
		$this->set_success_title( esc_html__( "Page isn't blocked from indexing", 'wds' ) );
		$this->set_failure_title( esc_html__( 'Page is blocked from indexing', 'wds' ) );
	}

	/**
	 * Gets the ID of the check.
	 *
	 * @return string
	 */
	public function get_id() {
		return self::ID;
	}
}