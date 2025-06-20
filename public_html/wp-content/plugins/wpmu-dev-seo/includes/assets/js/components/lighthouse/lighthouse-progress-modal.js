import React from 'react';
import { __ } from '@wordpress/i18n';
import Button from '../button';
import Modal from '../modal';
import ProgressBar from '../progress-bar';
import MascotMessage from '../mascot-message';
import ConfigValues from '../../es6/config-values';

const isMember = ConfigValues.get('is_member', 'admin') === '1';

export default class LighthouseProgressModal extends React.Component {
	static defaultProps = {
		isMember: false,
		progress: 0,
		statusMessage: __('Initializing engines…', 'wds'),
		onClose: () => false,
	};

	render() {
		const { progress, statusMessage, onClose } = this.props;

		return (
			<Modal
				id="wds-lighthouse-progress-modal"
				className={!!isMember && 'is-member'}
				title={__('SEO Test in progress', 'wds')}
				description={__(
					'Your SEO test is in progress, please wait a few moments…',
					'wds'
				)}
				onClose={onClose}
			>
				<ProgressBar progress={progress} stateMessage={statusMessage} />
				{!isMember && (
					<MascotMessage
						message={__(
							'Upgrade to Pro to schedule automated tests and send white label email reports directly to your clients. Never miss a beat with your search engine optimization.',
							'wds'
						)}
						button={
							<Button
								color="purple"
								target="_blank"
								href="https://wpmudev.com/project/smartcrawl-wordpress-seo/?utm_source=smartcrawl&utm_medium=plugin&utm_campaign=smartcrawl_lighthouse_modal_upsell_notice"
								text={__(
									'Unlock now with Pro',
									'wds'
								)}
							/>
						}
					/>
				)}
			</Modal>
		);
	}
}
