<?php
/**
 * Manages robots.txt functionality.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Modules\Advanced\Robots;

use SmartCrawl\Admin\Settings\Admin_Settings;
use SmartCrawl\Controllers;
use SmartCrawl\Simple_Renderer;
use SmartCrawl\Singleton;

/**
 * Robots.txt Controller class.
 */
class Controller extends Controllers\Submodule_Controller {

	use Singleton;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		$this->module_title = __( 'Robots.txt Editor', 'wds' );
		$this->event_name   = 'Robots.txt Editor';
	}

	/**
	 * Should this module run?.
	 *
	 * @return bool
	 */
	public function should_run() {
		return ! $this->file_exists() && $this->rootdir_exists() && parent::should_run();
	}

	/**
	 * Includes methods which runs always.
	 *
	 * @return void
	 */
	protected function always() {
		parent::always();

		add_filter( "smartcrawl_settings_{$this->parent->module_id}_submodules", array( $this, 'update_settings_submodules' ) );
	}

	/**
	 * Initialization method.
	 *
	 * @return void
	 */
	protected function init() {
		parent::init();

		add_action( 'template_redirect', array( $this, 'hook_robots' ), 999 );
	}

	/**
	 * Includes methods when the controller stops running.
	 *
	 * @return void
	 */
	protected function terminate() {
		parent::terminate();

		remove_action( 'template_redirect', array( $this, 'hook_robots' ), 999 );
	}

	/**
	 * Removes all action hooks for `do_robots` and serves SmartCrawl's robots.txt.
	 *
	 * @return void
	 */
	public function hook_robots() {
		remove_all_actions( 'do_robots' );
		add_action( 'do_robots', array( $this, 'serve_robots_file' ) );
	}

	/**
	 * Serves SmartCrawl's robots.txt file.
	 *
	 * @return void
	 */
	public function serve_robots_file() {
		$file_contents = $this->get_content();
		$this->output_text( $file_contents );
	}

	/**
	 * Checks whether robots.txt file exists or not.
	 *
	 * @return bool
	 */
	public function file_exists() {
		return file_exists( ABSPATH . 'robots.txt' );
	}

	/**
	 * Checks if rootdir is exsiting.
	 *
	 * @return bool
	 */
	public function rootdir_exists() {
		$url_components = wp_parse_url( home_url() );

		return empty( $url_components['path'] ) || '/' === $url_components['path'];
	}

	/**
	 * Outputs text.
	 *
	 * @param string $text Text to output.
	 *
	 * @return void
	 */
	private function output_text( $text ) {
		if ( ! headers_sent() ) {
			status_header( 200 );
			header( 'Content-Type: text/plain; charset=UTF-8' );

			die( $text ); // phpcs:ignore
		}
	}

	/**
	 * Retrieves sitemap url.
	 *
	 * @return string
	 */
	public function get_sitemap_url() {
		$disabled = (bool) \smartcrawl_get_array_value( $this->options, 'sitemap_directive_disabled' );

		if ( $disabled ) {
			return '';
		}

		$sc_sitemap_enabled = \SmartCrawl\Sitemaps\Utils::sitemap_enabled();

		if ( $sc_sitemap_enabled ) {
			return \smartcrawl_get_sitemap_url();
		}

		$custom_url = trim( (string) \smartcrawl_get_array_value( $this->options, 'custom_sitemap_url' ) );

		if ( empty( $custom_url ) ) {
			return '';
		}

		return strpos( $custom_url, 'http' ) === 0
			? $custom_url
			: home_url( $custom_url );
	}

	/**
	 * Retrieves custom directives.
	 *
	 * @return array|mixed|string
	 */
	public function get_custom_directives() {
		$options      = $this->get_options();
		$option_value = \smartcrawl_get_array_value( $options, 'custom_directives' );

		if ( ! empty( $option_value ) ) {
			return $option_value;
		}

		return "User-agent: *\nDisallow:";
	}

	/**
	 * Retrieves robots.txt file content.
	 *
	 * @return string
	 */
	public function get_content() {
		$contents = $this->get_custom_directives();

		$sitemap_url = $this->get_sitemap_url();

		if ( $sitemap_url ) {
			$contents = sprintf( "%s\n\nSitemap: %s", $contents, $sitemap_url );
		}

		$contents = apply_filters_deprecated(
			'wds_robots_txt_content',
			array( $contents ),
			'6.4.2',
			'smartcrawl_robots_txt_content',
			__( 'Please use our new filter `smartcrawl_robots_txt_content` in SmartCrawl.', 'wds' )
		);

		return apply_filters( 'smartcrawl_robots_txt_content', $contents );
	}

	/**
	 * Sanitizes submitted options
	 *
	 * @param array $input Raw input.
	 *
	 * @return bool True if sanitized successfully, otherwise false.
	 */
	public function sanitize_options( $input ) {
		$old_options = $this->options;

		if ( isset( $input['active'] ) ) {
			$active = boolval( $input['active'] );

			if ( empty( $this->options['active'] ) || $active !== $this->options['active'] ) {
				$this->options['active'] = $active;

				return true;
			}

			unset( $input['active'] );
		}

		if ( empty( $input ) ) {
			return true;
		}

		$this->options['sitemap_directive_disabled'] = ! empty( $input['sitemap_directive_disabled'] );

		if ( ! $this->options['sitemap_directive_disabled'] && \SmartCrawl\Sitemaps\Utils::sitemap_enabled() ) {
			$this->options['custom_sitemap_url'] = '';
		} elseif ( isset( $input['custom_sitemap_url'] ) ) {
			$this->options['custom_sitemap_url'] = esc_url_raw( $input['custom_sitemap_url'] );
		}

		if ( isset( $input['custom_directives'] ) ) {
			$this->options['custom_directives'] = sanitize_textarea_field( $input['custom_directives'] );
		}

		return true;
	}

	/**
	 * Returns localize script data.
	 *
	 * @return array
	 */
	public function localize_script_args() {
		return array(
			'active'          => ! empty( $this->options['active'] ),
			'already_exist'   => $this->file_exists(),
			'rootdir_exist'   => $this->rootdir_exists(),
			'sitemap_enabled' => \SmartCrawl\Sitemaps\Utils::sitemap_enabled(),
			'override_native' => \SmartCrawl\Sitemaps\Utils::override_native(),
			'robots_output'   => $this->get_content(),
			'sitemap_url'     => \smartcrawl_get_sitemap_url(),
			'options'         => array(
				'sitemap_directive_disabled' => isset( $this->options['sitemap_directive_disabled'] ) ? $this->options['sitemap_directive_disabled'] : true,
				'custom_sitemap_url'         => isset( $this->options['custom_sitemap_url'] ) ? $this->options['custom_sitemap_url'] : '',
				'custom_directives'          => $this->get_custom_directives(),
			),
		);
	}

	/**
	 * Default options.
	 *
	 * @return array()
	 */
	public function defaults() {
		return array(
			'sitemap_directive_disabled' => true,
		);
	}

	/**
	 * Updates settings submodules.
	 *
	 * @param array $submodules Submodules data with activation status.
	 *
	 * @return array
	 */
	public function update_settings_submodules( $submodules ) {
		$robots_submodule = $submodules[ $this->module_id ];

		if ( $this->file_exists() ) {
			$robots_submodule['warning'] = sprintf(
				/* translators: %s: Url to robots.txt file. */
				__( 'We\'ve detected an existing <a target="_blank" href="%s">robots.txt</a> file that we are unable to edit. You will need to remove it before you can enable this feature.', 'wds' ),
				\smartcrawl_get_robots_url()
			);
		}

		$submodules[ $this->module_id ] = $robots_submodule;

		return $submodules;
	}

	/**
	 * Outputs submodule content to dashboard widget.
	 *
	 * @return void
	 */
	public function render_dashboard_content() {
		$active = (bool) $this->should_run();
		?>

		<div class="wds-separator-top wds-draw-left-padded <?php echo $active ? 'wds-space-between' : ''; ?>">
			<small>
				<strong><?php echo esc_html( $this->module_title ); ?></strong>
			</small>

			<?php
			if ( $this->file_exists() ) {
				Simple_Renderer::render(
					'notice',
					array(
						'message' => \smartcrawl_format_link(
						// translators: %s link to robots.txt file.
							esc_html__( "We've detected an existing %s file that we are unable to edit. You will need to remove it before you can enable this feature.", 'wds' ),
							\smartcrawl_get_robots_url(),
							'robots.txt',
							'_blank'
						),
					)
				);

				return;
			}
			?>

			<?php if ( $active ) : ?>

				<span class="wds-right">
					<span class="sui-tag wds-right sui-tag-sm sui-tag-blue"><?php esc_html_e( 'Active', 'wds' ); ?></span>
				</span>

			<?php else : ?>

				<p>
					<small><?php esc_html_e( 'Add a robots.txt file to tell search engines what they can and can’t index, and where things are.', 'wds' ); ?></small>
				</p>

				<button
					type="button"
					data-module="<?php echo esc_attr( $this->parent->module_id ); ?>"
					data-submodule="<?php echo esc_attr( $this->module_id ); ?>"
					class="wds-activate-submodule wds-disabled-during-request sui-button sui-button-blue">
					<span class="sui-loading-text"><?php esc_html_e( 'Activate', 'wds' ); ?></span>
					<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
				</button>

			<?php endif; ?>
		</div>

		<?php
	}
}