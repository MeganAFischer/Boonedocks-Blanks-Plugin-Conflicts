<?php
/**
 * Site_Settings class for handling site settings schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Sources;

/**
 * Class Site_Settings
 *
 * Handles site settings schema fragments.
 */
class Site_Settings extends Property {
	const ID          = 'site_settings';
	const NAME        = 'site_name';
	const DESCRIPTION = 'site_description';
	const URL         = 'site_url';
	const ADMIN_EMAIL = 'site_admin_email';

	/**
	 * The site setting.
	 *
	 * @var string
	 */
	private $setting;

	/**
	 * Site_Settings constructor.
	 *
	 * @param string $setting The site setting.
	 */
	public function __construct( $setting ) {
		parent::__construct();

		$this->setting = $setting;
	}

	/**
	 * Retrieves the value of the site setting.
	 *
	 * @return mixed|string|void The value of the site setting.
	 */
	public function get_value() {
		$setting = str_replace( 'site_', '', $this->setting );
		return get_bloginfo( $setting );
	}
}