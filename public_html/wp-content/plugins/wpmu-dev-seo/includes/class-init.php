<?php
/**
 * General plugin initialization
 *
 * @package SmartCrawl
 */

namespace SmartCrawl;

/**
 * Class Init.
 */
class Init {

	/**
	 * Inits plugin
	 *
	 * @return  void
	 */
	public function __construct() {

		// Load the plugin text domain.
		add_action( 'after_setup_theme', array( $this, 'textdomain' ) );

		$this->functions();
		$this->init();
	}

	/**
	 * Inits the class.
	 *
	 * @return void
	 */
	private function init() {
		$this->common();

		add_action( 'wpml_loaded', array( $this, 'wpml' ) );

		if ( is_admin() ) {
			$this->admin();
		} else {
			$this->front();
		}

		// Boot up the hub controller.
		Controllers\Hub::serve();
		Third_Party_Import\Controller::serve();
	}

	/**
	 * Inits common functionality.
	 *
	 * @since 3.6.0
	 *
	 * @return void
	 */
	private function common() {
		Upgrade\Controller::get()->run();
		Modules\Advanced\Controller::get()->run();

		Cache\Manager::get()->run();
		Sitemaps\Front::get()->run();
		Sitemaps\Native::get()->run();
		Sitemaps\Controller::get()->run();
		Sitemaps\Troubleshooting::get()->run();
		Sitemaps\Dashboard_Widget::get()->run();
		Controllers\Cron::get()->run();
		Controllers\Compatibility::get()->run();
		Controllers\Analysis_Content::get()->run();
		Controllers\Data::get()->run();
		Controllers\Plugin_Links::get()->run();
		Admin\Admin::get()->run();
		Schema\Media::get()->run();
		Admin\Pages\Upgrade::get()->run();
		Schema\Types::get()->run();
		Schema\Printer::run();
		Lighthouse\Controller::get()->run();
		Configs\Controller::get()->run();
		Crawler\Controller::get()->run();
		Multisite\Network_Configs::get()->run();
		Multisite\Sitewide_Deprecation::get()->run();
		Controllers\Ajax_Search::get()->run();
		Controllers\Primary_Terms::get()->run();
		Mixpanel\Sitemap::get()->run();
		Controllers\Cross_Sell_page::get()->run();
	}

	/**
	 * Inits admin side functionality.
	 *
	 * @since 3.5.2
	 *
	 * @return void
	 */
	private function admin() {
		Controllers\Recommended_Plugins::get()->run();
		Controllers\Dash_Notices::get()->run();
		Controllers\New_Feature::get()->run();
		Controllers\Onboard::get()->run();
		Controllers\Analysis::get()->run();
		Controllers\Assets::get()->run();
		Controllers\White_Label::get()->run();
		Admin\Conflict_Detector::get()->run();
		Admin\Survey::get()->run();
		Admin\Metabox::get()->run();
		Admin\Taxonomy::get()->run();
		Admin\Pages\Network_Settings::get()->run();
		Mixpanel\Dashboard::get()->run();
		Mixpanel\General::get()->run();
		Mixpanel\Schema::get()->run();
		Mixpanel\Advanced::get()->run();
		Mixpanel\Schema::get()->run();
		Mixpanel\Modules::get()->run();
		Mixpanel\Survey::get()->run();
		Mixpanel\Conflict_Detector::get()->run();
		Controllers\Welcome::get()->run();
	}

	/**
	 * Inits public facing side functionality.
	 *
	 * @since 3.5.2
	 *
	 * @return void
	 */
	private function front() {
		Controllers\OnPage::get()->run();
		Social\Controller::get()->run();
		Front::get()->run();
		Controllers\Report_Permalinks::get()->run();
	}

	/**
	 * Inits WPML integration.
	 *
	 * @since 3.7.2
	 *
	 * @return void
	 */
	public function wpml() {
		WPML\Controller::get()->run();
	}

	/**
	 * Inits public functions.
	 *
	 * @since 3.6.0
	 *
	 * @return void
	 */
	private function functions() {
		require_once SMARTCRAWL_PLUGIN_DIR . 'core/core.php';
	}

	/**
	 * Loads the plugin text domain.
	 *
	 * @since 3.5.2
	 *
	 * @return void
	 */
	public function textdomain() {
		if ( defined( '\WPMU_PLUGIN_DIR' ) && file_exists( \WPMU_PLUGIN_DIR . '/wpmu-dev-seo.php' ) ) {
			load_muplugin_textdomain( 'wds', dirname( \SMARTCRAWL_PLUGIN_BASENAME ) . '/languages' );
		} else {
			load_plugin_textdomain( 'wds', false, dirname( \SMARTCRAWL_PLUGIN_BASENAME ) . '/languages' );
		}
	}
}