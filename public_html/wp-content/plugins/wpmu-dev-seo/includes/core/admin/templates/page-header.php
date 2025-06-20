<?php
/**
 * Template: Page Header.
 *
 * @package Smartcrwal
 */

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
$title                 = empty( $title ) ? '' : $title;
$documentation_chapter = empty( $documentation_chapter ) ? '' : $documentation_chapter;
$utm_campaign          = empty( $utm_campaign ) ? '' : $utm_campaign;
$left_actions          = empty( $left_actions ) ? '' : $left_actions;
$left_actions_args     = empty( $left_actions_args ) ? array() : $left_actions_args;
$extra_actions         = empty( $extra_actions ) ? '' : $extra_actions;
$extra_actions_args    = empty( $extra_actions_args ) ? array() : $extra_actions_args;
?>

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

<div class="sui-header">
	<h1 class="sui-header-title"><?php echo esc_html( $title ); ?></h1>

	<?php if ( $left_actions ) : ?>
		<div class="sui-actions-left">
			<?php $this->render_view( $left_actions, $left_actions_args ); ?>
		</div>
	<?php endif; ?>

	<?php if ( $documentation_chapter ) : ?>
		<div class="sui-actions-right">
			<?php $this->render_view( $extra_actions, $extra_actions_args ); ?>

			<a
				target="_blank" class="sui-button sui-button-ghost wds-docs-button"
				href="https://wpmudev.com/docs/wpmu-dev-plugins/smartcrawl/?utm_source=smartcrawl&utm_medium=plugin&utm_campaign=<?php echo esc_attr( $utm_campaign ); ?>#<?php echo esc_attr( $documentation_chapter ); ?>"
			>
				<span class="sui-icon-academy" aria-hidden="true"></span>
				<?php esc_html_e( 'View Documentation', 'wds' ); ?>
			</a>
		</div>
	<?php endif; ?>
</div>