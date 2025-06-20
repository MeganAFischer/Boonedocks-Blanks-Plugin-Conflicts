import React from 'react';
import { __ } from '@wordpress/i18n';

export default class EmailRecipient extends React.Component {
	static defaultProps = {
		index: '',
		recipient: '',
		fieldName: '',
		onRemove: () => false,
	};

	constructor(props) {
		super(props);

		this.state = {};
	}

	handleRemove(e) {
		const { index, onRemove } = this.props;

		e.preventDefault();

		onRemove(index);
	}

	render() {
		const { index, recipient, fieldName } = this.props;

		return (
			<div className="wds-recipient sui-recipient">
				<span className="sui-recipient-name">{recipient.name}</span>
				<span className="sui-recipient-email">{recipient.email}</span>
				<span>
					{fieldName && (
						<a
							className="sui-button-icon"
							href="#"
							aria-label={__(
								'Delete email recipient',
								'wds'
							)}
							onClick={(e) => this.handleRemove(e)}
						>
							<span
								className="sui-icon-trash"
								aria-hidden="true"
							/>
						</a>
					)}
				</span>

				{fieldName && (
					<React.Fragment>
						<input
							type="hidden"
							name={fieldName + '[' + index + '][name]'}
							value={recipient.name}
						/>
						<input
							type="hidden"
							name={fieldName + '[' + index + '][email]'}
							value={recipient.email}
						/>
					</React.Fragment>
				)}
			</div>
		);
	}
}
