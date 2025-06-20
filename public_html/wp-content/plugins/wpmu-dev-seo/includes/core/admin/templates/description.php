<?php
/**
 * Template: Description.
 *
 * @package SmartCrawl
 */

$description = empty( $description ) ? '' : $description;
?>
<span class="sui-description"><?php echo wp_kses_post( $description ); ?></span>