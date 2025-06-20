<?php
/**
 * SEO_Meta class for handling SEO metadata in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Sources;

/**
 * Class SEO_Meta
 *
 * Handles SEO metadata.
 */
class SEO_Meta extends Property {
	const ID          = 'seo_meta';
	const TITLE       = 'seo_title';
	const DESCRIPTION = 'seo_description';

	/**
	 * The field to retrieve the SEO metadata for.
	 *
	 * @var string
	 */
	private $field;

	/**
	 * SEO_Meta constructor.
	 *
	 * @param string $field The field to retrieve the SEO metadata for.
	 */
	public function __construct( $field ) {
		parent::__construct();

		$this->field = $field;
	}

	/**
	 * Retrieves the value of the SEO metadata.
	 *
	 * @return mixed|string The value of the SEO metadata.
	 */
	public function get_value() {
		$resolver = \SmartCrawl\Endpoint_Resolver::resolve();
		$entity   = $resolver->get_queried_entity();

		if ( ! $entity ) {
			return '';
		}

		if ( self::TITLE === $this->field ) {
			return $entity->get_meta_title();
		} else {
			return $entity->get_meta_description();
		}
	}
}