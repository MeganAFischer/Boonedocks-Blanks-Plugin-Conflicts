<?php
/**
 * Class Twitter_Printer
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Social;

use SmartCrawl\Endpoint_Resolver;
use SmartCrawl\Work_Unit;
use SmartCrawl\Settings;
use SmartCrawl\Singleton;

/**
 * Outputs Twitter cards data to the page
 */
class Twitter_Printer extends Work_Unit {

	use Singleton;

	const CARD_SUMMARY = 'summary';

	const CARD_IMAGE = 'summary_large_image';

	/**
	 * Is running flag.
	 *
	 * @var bool $is_running
	 */
	private $is_running = false;

	/**
	 * Is done flag.
	 *
	 * @var bool $is_done
	 */
	private $is_done = false;

	/**
	 * Boot the hooking part.
	 */
	public static function run() {
		self::get()->add_hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	private function add_hooks() {
		// Do not double-bind.
		if ( $this->apply_filters( 'is_running', $this->is_running ) ) {
			return;
		}

		add_action( 'wp_head', array( $this, 'dispatch_tags_injection' ), 50 );
		add_action( 'smartcrawl_head_after_output', array( $this, 'dispatch_tags_injection' ) );

		$this->is_running = true;
	}

	/**
	 * Dispatch tag injection.
	 *
	 * @return bool
	 */
	public function dispatch_tags_injection() {
		if ( ! ! $this->is_done ) {
			return false;
		}
		$this->is_done = true;

		$queried = Endpoint_Resolver::resolve()->get_queried_entity();

		if (
			! $this->is_globally_enabled() ||
			! $queried ||
			! $queried->is_twitter_enabled()
		) {
			return false;
		}

		$images = $queried->get_twitter_images();
		$card   = $this->get_card_content( $images );
		$this->print_html_tag( 'card', $card );

		$site = $this->get_site_content();
		if ( ! empty( $site ) ) {
			$this->print_html_tag( 'site', $site );
		}

		$title = $queried->get_twitter_title();
		if ( ! empty( $title ) ) {
			$this->print_html_tag( 'title', $title );
		}

		$desc = $queried->get_twitter_description();
		if ( ! empty( $desc ) ) {
			$this->print_html_tag( 'description', $desc );
		}

		if ( ! empty( $images ) && is_array( $images ) ) {
			$twitter_image_url = array_keys( $images )[0];
			if ( $twitter_image_url ) {
				$this->print_html_tag( 'image', $twitter_image_url );
			}
		}

		return true;
	}

	/**
	 * Is module globally enabled.
	 *
	 * @return bool
	 */
	private function is_globally_enabled() {
		$settings = Settings::get_options();

		return ! empty( $settings['twitter-card-enable'] );
	}

	/**
	 * Card type to render.
	 *
	 * @param array $images Images list.
	 *
	 * @return string Card type
	 */
	public function get_card_content( $images = array() ) {
		$options = Settings::get_component_options( Settings::COMP_SOCIAL );
		$card    = is_array( $options ) && ! empty( $options['twitter-card-type'] )
			? $options['twitter-card-type']
			: self::CARD_IMAGE;

		if ( self::CARD_IMAGE === $card ) {
			// Force summary card if we can't show image.
			if ( empty( $images ) ) {
				$card = self::CARD_SUMMARY;
			}
		}

		return $card;
	}

	/**
	 * Gets HTML element ready for rendering.
	 *
	 * @param string $type    Element type to prepare.
	 * @param string $content Element content.
	 *
	 * @return string Element
	 */
	public function get_html_tag( $type, $content ) {
		$content = apply_filters( 'wds_custom_twitter_meta', $content, $type );

		return '<meta name="twitter:' . esc_attr( $type ) . '" content="' . esc_attr( $content ) . '" />' . "\n";
	}

	/**
	 * Sitewide twitter handle.
	 *
	 * @return string Handle.
	 */
	public function get_site_content() {
		$options = Settings::get_component_options( Settings::COMP_SOCIAL );

		return is_array( $options ) && ! empty( $options['twitter_username'] )
			? $options['twitter_username']
			: '';
	}

	/**
	 * Get prefix for filters.
	 *
	 * @return string
	 */
	public function get_filter_prefix() {
		return 'wds-twitter';
	}

	/**
	 * Get allowed tags.
	 *
	 * @return array[][]
	 */
	private function get_allowed_tags() {
		return array(
			'meta' => array(
				'name'    => array(),
				'content' => array(),
			),
		);
	}

	/**
	 * Print html tag content.
	 *
	 * @param string $type    Tag type.
	 * @param string $content Tag content.
	 *
	 * @return void
	 */
	private function print_html_tag( $type, $content ) {
		echo wp_kses( $this->get_html_tag( $type, $content ), $this->get_allowed_tags() );
	}
}