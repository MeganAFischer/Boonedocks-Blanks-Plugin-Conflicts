<?php
/**
 * Template: Dashboard Configs Widget.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl;

// Rendered through react.
wp_enqueue_script( \SmartCrawl\Controllers\Assets::CONFIGS_JS );			   		    		  				
?>
<div id="wds-config-widget" class="wds-configs-container"></div>