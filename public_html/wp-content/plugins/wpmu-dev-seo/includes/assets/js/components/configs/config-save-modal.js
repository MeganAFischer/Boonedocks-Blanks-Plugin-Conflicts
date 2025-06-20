import React from 'react';
import Modal from '../modal';
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import Button from '../button';
import {
	hasWhitelistCharactersOnly,
	isNonEmpty,
	isValuePlainText,
	Validator,
} from '../../utils/validators';
import fieldWithValidation from '../field-with-validation';
import TextInputField from '../form-fields/text-input-field';
import TextField from '../form-fields/text-field';
import ConfigValues from '../../es6/config-values';

const configNameValidator = new Validator(
	hasWhitelistCharactersOnly,
	__(
		"Invalid config name. Use only alphanumeric characters (a-z, A-Z, 0-9) and allowed special characters (@.'_-).",
		'wds'
	)
);
const ConfigNameField = fieldWithValidation(TextInputField, [
	isNonEmpty,
	isValuePlainText,
	configNameValidator,
]);
const ConfigDescriptionField = fieldWithValidation(TextField, [
	isValuePlainText,
]);

export default class ConfigSaveModal extends React.Component {
	static defaultProps = {
		configName: '',
		configDescription: '',
		inProgress: false,
		editMode: false,
		onClose: () => false,
		onSave: () => false,
	};

	constructor(props) {
		super(props);

		const editMode = this.props.editMode;

		this.props = props;
		this.state = {
			configName: this.props.configName,
			configNameValid: editMode,
			configDescription: this.props.configDescription,
			configDescriptionValid: true,
		};
	}

	handleNameChange(value, isValid) {
		this.setState({
			configName: value,
			configNameValid: isValid,
		});
	}

	handleDescriptionChange(value, isValid) {
		this.setState({
			configDescription: value,
			configDescriptionValid: isValid,
		});
	}

	render() {
		let modalTitle, modalDescription, nameFieldLabel;
		if (this.props.configName) {
			modalTitle = __('Rename Config', 'wds');
			modalDescription = __(
				'Change your config name to something recognizable.',
				'wds'
			);
			nameFieldLabel = __('New Config Name', 'wds');
		} else {
			modalTitle = __('Save Config', 'wds');
			modalDescription = createInterpolateElement(
				sprintf(
					// translators: 1: plugin title
					__(
						"Save your current <strong>%1$s</strong> settings configurations. You'll be able to then download and apply it to your other sites with <strong>%1$s</strong> installed.",
						'wds'
					),
					ConfigValues.get('plugin_title', 'admin')
				),
				{ strong: <strong /> }
			);
			nameFieldLabel = __('Config Name', 'wds');
		}

		const onSubmit = () =>
			this.props.onSave(
				this.state.configName,
				this.state.configDescription
			);
		const submissionDisabled =
			!this.state.configNameValid || !this.state.configDescriptionValid;

		return (
			<Modal
				id="wds-config-modal"
				title={modalTitle}
				description={modalDescription}
				onClose={() => this.props.onClose()}
				disableCloseButton={this.props.inProgress}
				small={true}
				enterDisabled={submissionDisabled}
				onEnter={onSubmit}
				focusAfterOpen="wds-config-name"
				footer={
					<React.Fragment>
						<div className="sui-flex-child-right">
							<Button
								text={__('Cancel', 'wds')}
								ghost={true}
								disabled={this.props.inProgress}
								onClick={this.props.onClose}
							/>
						</div>

						<div className="sui-actions-right">
							<Button
								text={__('Save', 'wds')}
								color="blue"
								disabled={submissionDisabled}
								onClick={onSubmit}
								loading={this.props.inProgress}
								icon="sui-icon-save"
							/>
						</div>
					</React.Fragment>
				}
			>
				<ConfigNameField
					id="wds-config-name"
					label={nameFieldLabel}
					value={this.state.configName}
					isRequired={true}
					onChange={(value, isValid) =>
						this.handleNameChange(value, isValid)
					}
					errorMessage={__(
						"Invalid config name. Use only alphanumeric characters (a-z, A-Z, 0-9) and allowed special characters (@.'_-).",
						'wds'
					)}
				/>

				<ConfigDescriptionField
					id="wds-config-description"
					label={__('Config Description', 'wds')}
					onChange={(value, isValid) =>
						this.handleDescriptionChange(value, isValid)
					}
					value={this.state.configDescription}
				/>
			</Modal>
		);
	}
}
