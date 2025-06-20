import React from 'react';
import update from 'immutability-helper';
import { __, sprintf } from '@wordpress/i18n';
import EmailRecipient from './email-receipient';
import EmailRecipientModal from './email-recipient-modal';
import Button from '../button';
import Notice from '../notices/notice';
import NoticeUtil from '../../utils/notice-util';
import FloatingNoticePlaceholder from '../floating-notice-placeholder';
import ConfigValues from '../../es6/config-values';

export default class EmailRecipients extends React.Component {
	constructor(props) {
		super(props);

		this.state = {
			recipients: ConfigValues.get('recipients', 'email_recipients'),
			openDialog: false,
		};
	}

	render() {
		const { recipients, openDialog } = this.state;

		const id = ConfigValues.get('id', 'email_recipients');
		const fieldName = ConfigValues.get('field_name', 'email_recipients');

		return (
			<React.Fragment>
				<FloatingNoticePlaceholder id="wds-email-recipient-notice" />
				{!recipients.length && (
					<Notice
						type="warning"
						message={__(
							"You've removed all recipients. If you save without a recipient, we'll automatically turn off reports.",
							'wds-texdomain'
						)}
					/>
				)}
				<div>
					{recipients.map((recipient, index) => (
						<EmailRecipient
							key={index}
							index={index}
							recipient={recipient}
							fieldName={fieldName}
							onRemove={(ind) => this.handleRemove(ind)}
						/>
					))}
				</div>

				<Button
					ghost={true}
					icon="sui-icon-plus"
					onClick={() => this.toggleModal()}
					text={__('Add Recipient', 'wds-texdomain')}
				/>
				{openDialog && (
					<EmailRecipientModal
						id={id}
						onSubmit={(name, email) => this.handleAdd(name, email)}
						onClose={() => this.toggleModal()}
					/>
				)}
			</React.Fragment>
		);
	}

	handleAdd(name, email) {
		this.setState(
			{
				recipients: update(
					[
						{
							name,
							email,
						},
					],
					{
						$push: this.state.recipients,
					}
				),
				openDialog: false,
			},
			() => {
				NoticeUtil.showInfoNotice(
					'wds-email-recipient-notice',
					sprintf(
						// translators: %s: Recipient's name.
						__(
							'%s has been added as a recipient. Please save your changes to set this live.',
							'wds-texdomain'
						),
						name
					),
					false
				);
			}
		);
	}

	handleRemove(index) {
		this.setState({
			recipients: update(this.state.recipients, {
				$splice: [[index, 1]],
			}),
		});
	}

	toggleModal() {
		this.setState({ openDialog: !this.state.openDialog });
	}
}
