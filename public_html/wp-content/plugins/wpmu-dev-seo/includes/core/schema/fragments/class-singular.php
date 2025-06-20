<?php
/**
 * Singular class for handling singular schema fragments in SmartCrawl.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Schema\Fragments;

use SmartCrawl\Entities;
use SmartCrawl\Models\User;
use SmartCrawl\Schema\Utils;

/**
 * Class Singular
 *
 * Handles singular schema fragments.
 */
class Singular extends Fragment {

	/**
	 * The post entity.
	 *
	 * @var Entities\Post
	 */
	private $post;

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
	 * Whether to include article schema.
	 *
	 * @var bool
	 */
	private $include_article_schema;

	/**
	 * Singular constructor.
	 *
	 * @param Entities\Post $post The post entity.
	 * @param bool          $include_article_schema Whether to include article schema.
	 */
	public function __construct( $post, $include_article_schema = true ) {
		$this->post                   = $post;
		$this->include_article_schema = $include_article_schema;
		$this->utils                  = Utils::get();
		$this->owner                  = User::owner();
	}

	/**
	 * Retrieves raw schema data.
	 *
	 * @return array|mixed The raw schema data.
	 */
	protected function get_raw() {
		$url         = $this->post->get_permalink();
		$title       = $this->post->get_meta_title();
		$description = $this->post->get_meta_description();

		$is_publisher_page = $this->is_publisher_output_page();
		$publisher         = new Publisher( $is_publisher_page );

		$schema = array(
			new Header( $url, $title, $description ),
			new Footer( $url, $title, $description ),
			$publisher,
			new Website(),
			new Breadcrumb(),
		);

		$author_id             = false;
		$author                = User::get( $this->post->get_wp_post()->post_author );
		$add_publishing_person = $is_publisher_page && $this->utils->is_schema_type_person();
		if ( $add_publishing_person ) {
			$publishing_person = new Publishing_Person( $publisher->get_publisher_url() );
			$schema[]          = $publishing_person;

			if ( $this->owner->get_id() === $author->get_id() ) {
				$author_id = $publishing_person->get_publishing_person_id();
			}
		}

		if ( ! $author_id ) {
			$post_author = new Post_Author( $this->post->get_post_author() );
			$schema[]    = $post_author;
			$author_id   = $post_author->get_post_author_id();
		}

		$custom_schema_types = $this->utils->get_custom_schema_types( $this->post->get_wp_post(), $this->post->is_front_page() );
		if ( $custom_schema_types ) {
			$schema[] = new Minimal_Webpage( $url, $publisher->get_publisher_id() );

			$schema = $this->utils->add_custom_schema_types(
				$schema,
				$custom_schema_types,
				$this->utils->get_webpage_id( $url )
			);
		} elseif ( $this->is_contact_page() || $this->is_about_page() ) {
			$webpage_type = $this->is_contact_page()
				? 'ContactPage'
				: 'AboutPage';

			$schema[] = new Webpage(
				$this->post,
				$webpage_type,
				$author_id,
				$publisher->get_publisher_id()
			);
		} elseif ( $this->include_article_schema ) {
			$schema[] = new Minimal_Webpage( $url, $publisher->get_publisher_id() );
			$schema[] = new Article(
				$this->post,
				$this->get_article_type(),
				$author_id,
				$publisher->get_publisher_id()
			);
		}

		$media = new Media( $this->post );
		foreach ( $media->get_schema() as $media_schema ) {
			$schema[] = $media_schema;
		}

		return $schema;
	}

	/**
	 * Retrieves the article type.
	 *
	 * @return string The article type.
	 */
	private function get_article_type() {
		return $this->show_news_article_schema()
			? 'NewsArticle'
			: 'Article';
	}

	/**
	 * Checks if the current page is an about page.
	 *
	 * @return bool|mixed True if it is an about page, false otherwise.
	 */
	private function is_about_page() {
		return $this->is_special_page( 'schema_about_page' );
	}

	/**
	 * Checks if the current page is a contact page.
	 *
	 * @return bool|mixed True if it is a contact page, false otherwise.
	 */
	private function is_contact_page() {
		return $this->is_special_page( 'schema_contact_page' );
	}

	/**
	 * Checks if the current page is a special page.
	 *
	 * @param string $key The key to check.
	 * @param bool   $default The default value.
	 *
	 * @return bool|mixed True if it is a special page, false otherwise.
	 */
	private function is_special_page( $key, $default = false ) {
		$output_page = $this->utils->get_special_page( $key );
		if ( ! $output_page ) {
			return $default;
		}

		return $this->post->get_post_id() === $output_page->ID;
	}

	/**
	 * Checks if the current page is a publisher output page.
	 *
	 * @return bool True if it is a publisher output page, false otherwise.
	 */
	private function is_publisher_output_page() {
		if ( $this->is_special_page( 'schema_output_page', $this->post->is_front_page() ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if news article schema should be shown.
	 *
	 * @return bool True if news article schema should be shown, false otherwise.
	 */
	private function show_news_article_schema() {
		if ( ! \SmartCrawl\Sitemaps\Utils::get_sitemap_option( 'enable-news-sitemap' ) ) {
			return false;
		}

		$news_query      = new \SmartCrawl\Sitemaps\News\Query();
		$supported_types = $news_query->get_supported_types();
		if ( ! in_array( $this->post->get_post_type(), $supported_types, true ) ) {
			return false;
		}

		$ignore_ids = $news_query->get_ignore_ids( array( $this->post->get_post_type() ) );

		if ( in_array( $this->post->get_post_id(), $ignore_ids, true ) ) {
			return false;
		}

		return true;
	}
}