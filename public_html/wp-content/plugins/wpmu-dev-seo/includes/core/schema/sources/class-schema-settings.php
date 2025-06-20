<?php
/**
 * Schema_Settings class for handling schema settings in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Sources;

use SmartCrawl\Schema\Utils;

/**
 * Class Schema_Settings
 *
 * Handles schema settings.
 */
class Schema_Settings extends Property {
	const ID = 'schema_settings';

	const SITE_NAME                 = 'sitename';
	const WEBSITE_LOGO              = 'schema_website_logo';
	const PERSON_BRAND_NAME         = 'person_brand_name';
	const PERSON_BRAND_LOGO         = 'person_brand_logo';
	const PERSON_PORTRAIT           = 'person_portrait';
	const ORGANIZATION_NAME         = 'organization_name';
	const ORGANIZATION_DESCRIPTION  = 'organization_description';
	const ORGANIZATION_LOGO         = 'organization_logo';
	const ORGANIZATION_LOGO_URL     = 'organization_logo_url';
	const ORGANIZATION_PHONE_NUMBER = 'organization_phone_number';

	/**
	 * The setting key.
	 *
	 * @var string
	 */
	private $setting_key;

	/**
	 * Schema_Settings constructor.
	 *
	 * @param string $setting_key The setting key.
	 */
	public function __construct( $setting_key ) {
		parent::__construct();

		$this->setting_key = $setting_key;
	}

	/**
	 * Retrieves the value of the specified setting.
	 *
	 * TODO: maybe return default values when setting value not available? For example site name when organization name is not available
	 *
	 * @return array|mixed|string|null The value of the setting.
	 */
	public function get_value() {
		$schema_option_value = $this->utils->get_schema_option( $this->setting_key );
		$social_option_value = $this->utils->get_social_option(
			self::ORGANIZATION_LOGO_URL === $this->setting_key
				? str_replace( '_url', '', $this->setting_key )
				: $this->setting_key
		);
		$site_url            = get_site_url();
		$schema_utils        = Utils::get();

		switch ( $this->setting_key ) {
			case self::ORGANIZATION_NAME:
				return $schema_utils->get_organization_name();

			case self::ORGANIZATION_DESCRIPTION:
				return $schema_utils->get_organization_description();

			case self::SITE_NAME:
			case self::ORGANIZATION_LOGO_URL:
				return $social_option_value;

			case self::WEBSITE_LOGO:
				return $this->utils->get_media_item_image_schema(
					$schema_option_value,
					$this->utils->url_to_id( $site_url, '#schema-site-logo' )
				);

			case self::PERSON_BRAND_LOGO:
				return $this->utils->get_media_item_image_schema(
					$schema_option_value,
					$this->utils->url_to_id( $site_url, '#schema-personal-brand-logo' )
				);

			case self::PERSON_PORTRAIT:
				return $this->utils->get_media_item_image_schema(
					$schema_option_value,
					$this->utils->url_to_id( $site_url, '#schema-publisher-portrait' )
				);

			case self::ORGANIZATION_LOGO:
				$org_logo_id        = \smartcrawl_get_attachment_id_by_url( $social_option_value );
				$org_logo_schema_id = $this->utils->url_to_id( $site_url, '#schema-organization-logo' );
				if ( $org_logo_id ) {
					return $this->utils->get_media_item_image_schema( $org_logo_id, $org_logo_schema_id );
				} elseif ( $social_option_value ) {
					return $this->utils->get_image_schema( $org_logo_schema_id, $social_option_value );
				} else {
					return array();
				}

			case self::PERSON_BRAND_NAME:
			case self::ORGANIZATION_PHONE_NUMBER:
			default:
				return $schema_option_value;
		}
	}
}