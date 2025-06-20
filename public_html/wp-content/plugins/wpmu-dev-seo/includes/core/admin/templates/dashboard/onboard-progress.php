<?php
/**
 * Template: Onboard Progress Content.
 *
 * @package SmartCrawl
 */

?>

<p><?php esc_html_e( 'Please wait a few moments while we activate those services', 'wds' ); ?></p>
<?php
$this->render_view(
	'progress-bar',
	array(
		'progress' => 0,
	)
);
?>