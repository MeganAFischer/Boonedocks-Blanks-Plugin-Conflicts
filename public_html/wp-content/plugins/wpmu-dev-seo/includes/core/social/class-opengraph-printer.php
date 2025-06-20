<?php
/**
 * Class OpenGraph_Printer
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Social;

use SmartCrawl\Endpoint_Resolver;
use SmartCrawl\Settings;
use SmartCrawl\Singleton;

/**
 * Outputs OG tags to the page
 */
class OpenGraph_Printer {

	use Singleton;

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
	 * Boot the hooking part
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
		if ( apply_filters( 'wds-opengraph-is_running', $this->is_running ) ) {
			return;
		}

		add_action( 'wp_head', array( $this, 'dispatch_og_tags_injection' ), 50 );
		add_action( 'smartcrawl_head_after_output', array( $this, 'dispatch_og_tags_injection' ) );

		$this->is_running = true;
	}

	/**
	 * First-line dispatching of OG tags injection.
	 */
	public function dispatch_og_tags_injection() {
		if ( ! ! $this->is_done ) {
			return false;
		}

		$settings = Settings::get_component_options( Settings::COMP_SOCIAL );
		if ( empty( $settings['og-enable'] ) ) {
			return false;
		}
		$this->inject_global_tags();

		$this->is_done = true;

		return $this->inject_og_tags();
	}

	/**
	 * Injects globally valid tags - regardless of context.
	 */
	public function inject_global_tags() {
		$settings = Settings::get_component_options( Settings::COMP_SOCIAL );
		if ( ! empty( $settings['fb-app-id'] ) ) {
			$this->print_og_tag( 'fb:app_id', $settings['fb-app-id'] );
		}
	}

	/**
	 * Actually prints the OG tag.
	 *
	 * @param string $tag   Tagname or tagname-like string to print.
	 * @param mixed  $value Tag value as string, or list of string tag values.
	 *
	 * @return bool
	 */
	public function print_og_tag( $tag, $value ) {
		if ( empty( $tag ) || empty( $value ) ) {
			return false;
		}

		$og_tag = $this->get_og_tag( $tag, $value );
		if ( empty( $og_tag ) ) {
			return false;
		}

		echo wp_kses( $og_tag, $this->get_allowed_tags() );

		return true;
	}

	/**
	 * Gets the markup for an OG tag
	 *
	 * @param string $tag   Tagname or tagname-like string to print.
	 * @param mixed  $value Tag value as string, or list of string tag values.
	 *
	 * @return string
	 */
	public function get_og_tag( $tag, $value ) {
		if ( empty( $tag ) || empty( $value ) ) {
			return false;
		}

		return '<meta property="' . esc_attr( $tag ) . '" content="' . esc_attr( $value ) . '" />' . "\n";
	}

	/**
	 * Attempt to use post-specific meta setup to resolve tag values.
	 *
	 * Fallback to generic, global values.
	 *
	 * @return bool
	 */
	public function inject_og_tags() {
		$queried = Endpoint_Resolver::resolve()->get_queried_entity();
		if ( ! $queried || ! $queried->is_opengraph_enabled() ) {
			return false;
		}

		$this->print_og_tags( $queried->get_opengraph_tags() );

		return true;
	}

	/**
	 * Print og tags.
	 *
	 * @param array $opengraph_tags Tags.
	 *
	 * @return void
	 */
	public function print_og_tags( $opengraph_tags ) {
		if ( empty( $opengraph_tags ) ) {
			return;
		}

		foreach ( $opengraph_tags as $opengraph_tag => $opengraph_value ) {
			if ( $opengraph_value && is_scalar( $opengraph_value ) ) {
				$this->print_og_tag( $opengraph_tag, $opengraph_value );
			} elseif ( is_array( $opengraph_value ) ) {
				$this->print_og_tags( $opengraph_value );
			}
		}
	}

	/**
	 * Get allowed tags.
	 *
	 * @return array
	 */
	private function get_allowed_tags() {
		return array(
			'meta' => array(
				'property' => array(),
				'content'  => array(),
			),
		);
	}
}