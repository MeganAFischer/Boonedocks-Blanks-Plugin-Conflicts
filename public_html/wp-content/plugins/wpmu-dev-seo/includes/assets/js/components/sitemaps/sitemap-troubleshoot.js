import SettingsRow from '../settings-row';
import { __ } from '@wordpress/i18n';
import Button from '../button';
import React from 'react';
import SitemapTroubleshootModal from './sitemap-troubleshoot-modal';
import FloatingNoticePlaceholder from '../floating-notice-placeholder';

export default class SitemapTroubleshoot extends React.Component {
	static defaultStatic = {
		nonce: '',
		sitemapUrl: '',
	};

	constructor(props) {
		super(props);

		this.state = {
			started: false,
		};
	}

	render() {
		const { started } = this.state;
		const { nonce, sitemapUrl } = this.props;

		return (
			<React.Fragment>
				<SettingsRow
					label={__('Troubleshoot Sitemap', 'wds')}
					description={__(
						'If your sitemap is not as expected, you can use this tool to identify the problem.',
						'wds'
					)}
				>
					<FloatingNoticePlaceholder id="wds-troubleshoot-notice" />

					<Button
						id="wds-open-troubleshooting-modal"
						text={__('Troubleshoot', 'wds')}
						ghost={true}
						onClick={() => this.startTroubleshooting()}
					/>
				</SettingsRow>

				{started && (
					<SitemapTroubleshootModal
						nonce={nonce}
						sitemapUrl={sitemapUrl}
						onClose={() => this.stopTroubleshooting()}
					/>
				)}
			</React.Fragment>
		);
	}

	startTroubleshooting() {
		this.setState({ started: true });
	}

	stopTroubleshooting() {
		this.setState({ started: false });
	}
}
