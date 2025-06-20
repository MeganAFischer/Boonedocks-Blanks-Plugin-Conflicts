import React from 'react';
import ConfigValues from '../es6/config-values';
import RequestUtil from '../utils/request-util';
import UpsellNotice from './notices/upsell-notice';

export default class MascotMessage extends React.Component {
	static defaultProps = {
		msgKey: '',
		message: '',
		button: false,
		dismissible: true,
		image: '',
	};

	constructor(props) {
		super(props);

		const dismissedMessages = ConfigValues.get(
			'dismissed_messages',
			'admin'
		);

		this.state = {
			isDismissed: !!dismissedMessages[this.props.msgKey],
		};
	}

	render() {
		if (this.state.isDismissed) {
			return '';
		}

		const { message, dismissible, image, button } = this.props;

		const pluginUrl = ConfigValues.get('plugin_url', 'admin');

		return (
			<div className="wds-mascot-message sui-box-settings-row">
				<UpsellNotice
					image={
						image ? `${pluginUrl}/assets/images/${image}.svg` : ''
					}
					alt={image}
					message={message}
					dismissible={dismissible}
					onDismiss={() => this.dismissMessage()}
					button={button}
				/>
			</div>
		);
	}

	dismissMessage() {
		this.setState({ isDismissed: true });

		if (this.props.msgKey) {
			RequestUtil.post(
				'wds_dismiss_message',
				ConfigValues.get('nonce', 'admin'),
				{ message: this.props.msgKey }
			);
		}
	}
}
