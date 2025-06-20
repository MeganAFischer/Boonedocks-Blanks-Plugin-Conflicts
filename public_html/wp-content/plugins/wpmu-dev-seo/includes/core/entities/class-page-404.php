<?php
/**
 * 404 Page Entity.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Entities;

/**
 * Page_404 Entity class.
 */
class Page_404 extends Entity {

	/**
	 * Loads meta title.
	 *
	 * @return string Meta title.
	 */
	protected function load_meta_title() {
		return $this->load_option_string_value(
			'404',
			array( $this, 'load_meta_title_from_options' ),
			function () {
				return 'Page not found %%sep%% %%sitename%%';
			}
		);
	}

	/**
	 * Loads meta description.
	 *
	 * @return string Meta description.
	 */
	protected function load_meta_description() {
		return $this->load_option_string_value(
			'404',
			array( $this, 'load_meta_desc_from_options' ),
			'__return_empty_string'
		);
	}

	/**
	 * Loads robots meta tag.
	 *
	 * @return string Emtpy for this entity.
	 */
	protected function load_robots() {
		return '';
	}

	/**
	 * Loads the canonical URL.
	 *
	 * @return string Empty URL for this entity.
	 */
	protected function load_canonical_url() {
		return '';
	}

	/**
	 * Loads schema.
	 *
	 * @return array Empty schema for this entity.
	 */
	protected function load_schema() {
		return array();
	}

	/**
	 * Loads opengraph enabled.
	 *
	 * @return bool False for this entity.
	 */
	protected function load_opengraph_enabled() {
		return false;
	}

	/**
	 * Loads OpenGraph title.
	 *
	 * @return string Empty title for this entity.
	 */
	protected function load_opengraph_title() {
		return '';
	}

	/**
	 * Loads OpenGraph description.
	 *
	 * @return string Empty description for this entity.
	 */
	protected function load_opengraph_description() {
		return '';
	}

	/**
	 * Loads OpenGraph images.
	 *
	 * @return array Empty array for this entity.
	 */
	protected function load_opengraph_images() {
		return array();
	}

	/**
	 * Loads Twitter enabled.
	 *
	 * @return bool False for this entity.
	 */
	protected function load_twitter_enabled() {
		return false;
	}

	/**
	 * Loads Twitter title.
	 *
	 * @return string Empty title for this entity.
	 */
	protected function load_twitter_title() {
		return '';
	}

	/**
	 * Loads Twitter description.
	 *
	 * @return string Empty description for this entity.
	 */
	protected function load_twitter_description() {
		return '';
	}

	/**
	 * Loads Twitter images.
	 *
	 * @return array Empty array for this entity.
	 */
	protected function load_twitter_images() {
		return array();
	}

	/**
	 * Returns the macros for a given subject.
	 *
	 * @param string $subject The subject to get macros for. Default is an empty string.
	 *
	 * @return array Empty array for this entity.
	 */
	public function get_macros( $subject = '' ) {
		return array();
	}
}