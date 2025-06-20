<?php
/**
 * Publisher class for handling publisher schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Fragments;

use SmartCrawl\Models\User;
use SmartCrawl\Schema\Utils;

/**
 * Class Publisher
 *
 * Handles publisher schema fragments.
 */
class Publisher extends Fragment {

	/**
	 * Full output flag.
	 *
	 * @var bool
	 */
	private $full_output;

	/**
	 * Schema utilities.
	 *
	 * @var Utils
	 */
	private $utils;

	/**
	 * Owner user.
	 *
	 * @var User
	 */
	private $owner;

	/**
	 * Constructor.
	 *
	 * @param bool $full_output The full output flag.
	 */
	public function __construct( $full_output ) {
		$this->full_output = $full_output;
		$this->utils       = new Utils();
		$this->owner       = User::owner();
	}

	/**
	 * Retrieves the publisher type.
	 *
	 * @return mixed|string The publisher type.
	 */
	private function get_publisher_type() {
		if ( $this->utils->is_schema_type_person() ) {
			return 'Organization';
		} else {
			return $this->full_output
				? $this->get_organization_type_option() // Only use the specific org type If we're showing the full output.
				: 'Organization';  // Otherwise use Organization.
		}
	}

	/**
	 * Retrieves raw schema data.
	 *
	 * @return array The raw schema data.
	 */
	protected function get_raw() {
		return $this->get_publisher_schema();
	}

	/**
	 * Retrieves the publisher ID.
	 *
	 * @return string The publisher ID.
	 */
	public function get_publisher_id() {
		if ( $this->utils->is_schema_type_person() ) {
			return $this->get_personal_brand_id();
		} else {
			return $this->get_publishing_organization_id();
		}
	}

	/**
	 * Retrieves the publisher schema.
	 *
	 * @return array The publisher schema.
	 */
	protected function get_publisher_schema() {
		if ( $this->utils->is_schema_type_person() ) {
			return $this->get_personal_brand_schema();
		} else {
			return $this->get_publishing_organization_schema( $this->full_output );
		}
	}

	/**
	 * Retrieves the personal brand schema.
	 *
	 * @return array The personal brand schema.
	 */
	private function get_personal_brand_schema() {
		// Summary.
		$schema = array(
			'@type' => 'Organization',
			'@id'   => $this->get_personal_brand_id(),
			'url'   => $this->get_publisher_url(),
		);

		// Name.
		$schema['name'] = $this->utils->get_personal_brand_name();

		// Logo.
		$site_url = get_site_url();
		$logo     = $this->utils->get_media_item_image_schema(
			(int) $this->utils->get_schema_option( 'person_brand_logo' ),
			$this->utils->url_to_id( $site_url, '#schema-personal-brand-logo' )
		);
		if ( $logo ) {
			$schema['logo'] = $logo;
		}

		return $schema;
	}

	/**
	 * Retrieves the publishing organization schema.
	 *
	 * @param bool $full The full output flag.
	 *
	 * @return array The publishing organization schema.
	 */
	private function get_publishing_organization_schema( $full ) {
		// Summary.
		$organization_type = $this->get_publisher_type();

		$schema = array(
			'@type' => $organization_type,
			'@id'   => $this->get_publishing_organization_id(),
			'url'   => $this->get_publisher_url(),
		);

		// Name.
		$schema['name'] = $this->utils->get_organization_name();

		// Logo.
		$org_logo = $this->get_organization_logo();
		if ( $org_logo ) {
			$schema['logo'] = $org_logo;

			if ( $full ) {
				$schema['image'] = $org_logo;
			}
		}

		if ( ! $full ) {
			return $this->filter_owner_data( $schema );
		}

		// Description.
		$schema['description'] = $this->utils->get_organization_description();

		// Contact point.
		$contact_point = $this->utils->get_contact_point(
			$this->utils->get_schema_option( 'organization_phone_number' ),
			(int) $this->utils->get_schema_option( 'organization_contact_page' ),
			$this->utils->get_schema_option( 'organization_contact_type' )
		);
		if ( $contact_point ) {
			$schema['contactPoint'] = $contact_point;
		}

		// Social URLs.
		$social_urls = $this->utils->get_social_urls();
		if ( $social_urls ) {
			$schema['sameAs'] = $social_urls;
		}

		return $this->filter_owner_data( $schema );
	}

	/**
	 * Filters owner data.
	 *
	 * @param array $data The data to filter.
	 *
	 * @return array The filtered data.
	 */
	private function filter_owner_data( $data ) {
		return $this->utils->apply_filters( 'owner-data', $data );
	}

	/**
	 * Retrieves the organization logo.
	 *
	 * @return array The organization logo schema.
	 */
	private function get_organization_logo() {
		$url = $this->utils->get_social_option( 'organization_logo' );
		if ( empty( $url ) ) {
			return array();
		}

		$schema = $this->utils->get_image_schema(
			$this->utils->url_to_id( get_site_url(), '#schema-organization-logo' ),
			esc_url( $url ),
			60,
			60
		);

		return $this->utils->apply_filters( 'site-logo', $schema );
	}

	/**
	 * Retrieves the organization type option.
	 *
	 * @return string The organization type option.
	 */
	private function get_organization_type_option() {
		$org_type = $this->utils->get_schema_option( 'organization_type' );

		// Since version 2.10 LocalBusiness is not supported as organization_type.
		// Instead, the users are encouraged to use the LocalBusiness type in the schema builder.
		if ( 'LocalBusiness' === $org_type ) {
			$org_type = '';
		}

		return $org_type
			? $org_type
			: 'Organization';
	}

	/**
	 * Retrieves the publishing organization ID.
	 *
	 * @return string The publishing organization ID.
	 */
	private function get_publishing_organization_id() {
		return $this->utils->url_to_id( $this->get_publisher_url(), '#schema-publishing-organization' );
	}

	/**
	 * Retrieves the personal brand ID.
	 *
	 * @return string The personal brand ID.
	 */
	private function get_personal_brand_id() {
		return $this->utils->url_to_id( $this->get_publisher_url(), '#schema-personal-brand' );
	}

	/**
	 * Retrieves the publisher URL.
	 *
	 * @return string The publisher URL.
	 */
	public function get_publisher_url() {
		$output_page = $this->utils->get_special_page( 'schema_output_page' );

		return $output_page
			? get_permalink( $output_page )
			: get_site_url();
	}
}