<?php
/**
 * Media class for handling media schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Sources;

/**
 * Class Media
 *
 * Handles media schema fragments.
 */
class Media extends Property {
	const OBJECT = 'image';
	const URL    = 'image_url';

	/**
	 * The ID of the media item.
	 *
	 * @var int
	 */
	private $media_id;

	/**
	 * The field to retrieve the media data for.
	 *
	 * @var string
	 */
	private $field;

	/**
	 * Media constructor.
	 *
	 * @param int    $media_id The ID of the media item.
	 * @param string $field The field to retrieve the media data for.
	 */
	public function __construct( $media_id, $field ) {
		parent::__construct();

		$this->media_id = $media_id;
		$this->field    = $field;
	}

	/**
	 * Retrieves the value of the media data.
	 *
	 * @return array|mixed|string The value of the media data.
	 */
	public function get_value() {
		if ( self::URL === $this->field ) {
			$image_source = $this->utils->get_attachment_image_source( $this->media_id );

			return $image_source
				? $image_source[0]
				: '';
		} else {
			return $this->utils->get_media_item_image_schema(
				$this->media_id,
				home_url( "/#image-$this->media_id" )
			);
		}
	}
}