<?php
/**
 * Template: Lighthouse Settings.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl;

$action_url  = empty( $_view['action_url'] ) ? '' : $_view['action_url'];
$option_name = empty( $_view['option_name'] ) ? '' : $_view['option_name'];

$this->render_view( 'before-page-container' );
$is_reporting_enabled = ! is_multisite() || is_network_admin() || is_main_site();
?>

<div id="container" class="<?php \smartcrawl_wrap_class( 'wds-seo-health-settings wds-lighthouse-settings' ); ?>">
	<?php
	$this->render_view(
		'page-header',
		array(
			'title'                 => esc_html__( 'SEO Health', 'wds' ),
			'documentation_chapter' => 'seo-health',
			'left_actions'          => 'lighthouse/lighthouse-header-actions',
			'extra_actions'         => 'lighthouse/lighthouse-extra-actions',
			'utm_campaign'          => 'smartcrawl_seo-health_docs',
		)
	);
	?>

	<?php
	$this->render_view(
		'floating-notices',
		array(
			'keys' => array(
				'wds-email-recipient-notice',
				'wds-lighthouse-audit-copied',
			),
		)
	);
	?>

	<?php $this->render_view( 'lighthouse/lighthouse-summary' ); ?>

	<div class="wds-vertical-tabs-container sui-row-with-sidenav">
		<?php
		$this->render_view(
			'lighthouse/lighthouse-side-nav',
			array(
				'is_reporting_enabled' => $is_reporting_enabled,
			)
		);
		?>

		<div id="wds-lighthouse-tab"></div>

		<?php
		if ( $is_reporting_enabled ) {
			$this->render_view( 'lighthouse/lighthouse-reporting-tab' );
		}
		$this->render_view( 'health/health-section-settings' );
		?>
	</div>

	<?php $this->render_view( 'footer' ); ?>
</div>