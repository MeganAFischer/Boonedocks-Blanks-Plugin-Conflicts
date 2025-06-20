<?php
/**
 * SEO Audit crawl results dispatching template
 *
 * @package SmartCrawl
 */

$report = empty( $report ) ? null : $report;

if ( ! $report ) {
	return;
}
?>
<div class="wds-crawl-results-report wds-report">
	<?php
	if ( $report->has_state_messages() ) {
		foreach ( $report->get_state_messages() as $state_message ) {
			$this->render_view(
				'notice',
				array(
					'message' => $state_message,
					'class'   => 'sui-notice-error',
				)
			);
		}
	}
	?>

	<div id="wds-url-crawler-report"></div>
</div>