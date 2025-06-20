<?php
/**
 * Publishing\_Person class for handling publishing person schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Fragments;

use SmartCrawl\Models\User;
use SmartCrawl\Schema\Utils;

/**
 * Class Publishing\_Person
 *
 * Handles publishing person schema fragments.
 */
class Publishing_Person extends Fragment {

	/**
	 * The URL of the publisher.
	 *
	 * @var string
	 */
	private $publisher_url;

	/**
	 * Schema utilities.
	 *
	 * @var Utils
	 */
	private $utils;

	/**
	 * The owner user.
	 *
	 * @var User
	 */
	private $owner;

	/**
	 * Publishing\_Person constructor.
	 *
	 * @param string $publisher_url The URL of the publisher.
	 */
	public function __construct( $publisher_url ) {
		$this->publisher_url = $publisher_url;
		$this->utils         = Utils::get();
		$this->owner         = User::owner();
	}

	/**
	 * Retrieves the publishing person ID.
	 *
	 * @return string The publishing person ID.
	 */
	public function get_publishing_person_id() {
		return $this->utils->url_to_id( $this->publisher_url, '#schema-publishing-person' );
	}

	/**
	 * Retrieves raw schema data.
	 *
	 * @return array The raw schema data.
	 */
	protected function get_raw() {
		$schema = array(
			'@type' => 'Person',
			'@id'   => $this->get_publishing_person_id(),
			'url'   => $this->publisher_url,
		);

		// Name.
		$name           = $this->utils->first_non_empty_string(
			$this->utils->get_social_option( 'override_name' ),
			$this->utils->get_user_full_name( $this->owner )
		);
		$schema['name'] = $name;

		// Description.
		$description = $this->utils->get_textarea_schema_option( 'person_bio' );
		$description = ! empty( $description ) ? $description : $this->owner->get_description();
		if ( $description ) {
			$schema['description'] = $description;
		}

		// Job.
		$job_title = $this->utils->get_schema_option( 'person_job_title' );
		if ( $job_title ) {
			$schema['jobTitle'] = $job_title;
		}

		// Image.
		$site_url = get_site_url();
		$image    = $this->utils->get_media_item_image_schema(
			(int) $this->utils->get_schema_option( 'person_portrait' ),
			$this->utils->url_to_id( $site_url, '#schema-publisher-portrait' )
		);
		if ( ! $image && $this->utils->is_author_gravatar_enabled() ) {
			$schema['image'] = $this->utils->get_image_schema(
				$this->utils->url_to_id( $site_url, '#schema-publisher-gravatar' ),
				$this->owner->get_avatar_url( 100 ),
				100,
				100,
				$name
			);
		}
		if ( $image ) {
			$schema['image'] = $image;
		}

		// Contact.
		$contact_point = $this->utils->get_contact_point(
			$this->utils->get_schema_option( 'person_phone_number' ),
			(int) $this->utils->get_schema_option( 'person_contact_page' )
		);
		if ( $contact_point ) {
			$schema['contactPoint'] = $contact_point;
		}

		// Social URLs.
		$social_urls = $this->utils->get_social_urls();
		if ( $social_urls ) {
			$schema['sameAs'] = $social_urls;
		}

		return $schema;
	}
}