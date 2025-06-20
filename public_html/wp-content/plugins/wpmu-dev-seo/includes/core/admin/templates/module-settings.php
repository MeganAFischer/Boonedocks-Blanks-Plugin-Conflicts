<?php
/**
 * Template: Renders module settings page content.
 *
 * @package SmartCrawl
 */

$this->render_view( 'before-page-container' );
?>

<div class="<?php \smartcrawl_wrap_class(); ?>">

	<?php
	do_action_deprecated(
		'wds_admin_notices',
		array(),
		'6.4.2',
		'smartcrawl_admin_notices',
		__( 'Please use our new hook `smartcrawl_admin_notices` in SmartCrawl.', 'wds' )
	);

	do_action( 'smartcrawl_admin_notices' );
	?>

	<div id="container"></div>

	<?php $this->render_view( 'upsell-modal' ); ?>
</div>