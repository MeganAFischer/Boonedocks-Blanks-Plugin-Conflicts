import React from 'react';
import ReactDom from 'react-dom/client';
import domReady from '@wordpress/dom-ready';
import ErrorBoundary from './components/error-boundry';
import EmailRecipients from './components/email/email-recipients';

domReady(() => {
	const container = document.getElementById('wds-email-recipients');

	if (container) {
		const root = ReactDom.createRoot(container);

		root.render(
			<ErrorBoundary>
				<EmailRecipients />
			</ErrorBoundary>
		);
	}
});
