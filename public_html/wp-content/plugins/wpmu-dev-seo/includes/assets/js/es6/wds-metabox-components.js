import ErrorBoundary from '../components/error-boundry';
import domReady from '@wordpress/dom-ready';
import React from 'react';
import ReactDom from 'react-dom/client';
import Metabox from '../modules/metabox/metabox';
import MetaboxOnpage from './metabox-onpage';
import ConfigValues from './config-values';

domReady(() => {
	const placeholder = document.getElementById('wds-metabox-container');

	if (placeholder) {
		const root = ReactDom.createRoot(placeholder);

		root.render(
			<ErrorBoundary>
				<Metabox />
			</ErrorBoundary>
		);
	}
});

(function ($) {
	/**
	 * Set cookie value each time the metabox is toggled.
	 */
	const $metabox = $('#wds-wds-meta-box');

	$metabox.on('click', function () {
		if ($(this).is('.closed')) {
			window.Wds.set_cookie('wds-seo-metabox', '');
		} else {
			window.Wds.set_cookie('wds-seo-metabox', 'open');
		}
	});

	if (ConfigValues.get_bool('onpage_active', 'metabox')) {
		new MetaboxOnpage();
	}

	// Set metabox state on page load based on cookie value.
	// Fixes: https://app.asana.com/0/0/580085427092951/f
	if ('open' === window.Wds.get_cookie('wds-seo-metabox')) {
		$metabox.removeClass('closed');
	}
})(jQuery);
