import React from 'react';
import { __ } from '@wordpress/i18n';
import Button from '../button';
import Modal from '../modal';
import TextInputField from '../form-fields/text-input-field';
import {
	isEmailValid,
	isNonEmpty,
	isValuePlainText,
	Validator,
} from '../../utils/validators';
import fieldWithValidation from '../field-with-validation';

const RecipientNameField = fieldWithValidation(TextInputField, [
	isNonEmpty,
	isValuePlainText,
]);
const recipientEmailValidator = new Validator(
	isEmailValid,
	__('Email is invalid.', 'wds-texdomain')
);
const RecipientEmailField = fieldWithValidation(TextInputField, [
	isNonEmpty,
	isValuePlainText,
	recipientEmailValidator,
]);

export default class EmailRecipientModal extends React.Component {
	static defaultProps = {
		id: '',
		onSubmit: () => false,
		onClose: () => false,
	};

	constructor(props) {
		super(props);

		this.state = {
			name: '',
			email: '',
			nameValid: false,
			emailValid: false,
		};
	}

	render() {
		const { id, onClose } = this.props;
		const { name, email, nameValid, emailValid } = this.state;
		const disabled = !nameValid || !emailValid;

		return (
			<Modal
				id={id}
				title={__('Add Recipient', 'wds-texdomain')}
				description={__(
					'Add as many recipients as you like, they will receive email reports as per the schedule you set.',
					'wds-texdomain'
				)}
				small={true}
				onEnter={() => this.handleSubmit()}
				enterDisabled={disabled}
				onClose={onClose}
				footer={
					<React.Fragment>
						<Button
							className="wds-cancel-button"
							ghost={true}
							text={__('Cancel', 'wds-texdomain')}
							onClick={onClose}
						/>
						<div className="sui-actions-right">
							<Button
								id="wds-add-email-recipient"
								text={__('Add', 'wds-texdomain')}
								onClick={() => this.handleSubmit()}
								disabled={disabled}
							/>
						</div>
					</React.Fragment>
				}
			>
				<RecipientNameField
					id="wds-recipient-name"
					label={__('First name', 'wds-texdomain')}
					placeholder={__('E.g. John', 'wds-texdomain')}
					value={name}
					onChange={(value, isValid) =>
						this.handleChangeName(value, isValid)
					}
					isValid={nameValid}
				/>
				<RecipientEmailField
					id="wds-recipient-email"
					label={__('Email address', 'wds-texdomain')}
					placeholder={__('E.g. john@doe.com', 'wds-texdomain')}
					value={email}
					onChange={(value, isValid) =>
						this.handleChangeEmail(value, isValid)
					}
					isValid={emailValid}
				/>
			</Modal>
		);
	}

	handleSubmit() {
		const { name, email } = this.state;

		this.props.onSubmit(name, email);
	}

	handleChangeName(value, isValid) {
		this.setState({
			name: value,
			nameValid: isValid,
		});
	}

	handleChangeEmail(value, isValid) {
		this.setState({
			email: value,
			emailValid: isValid,
		});
	}
}
