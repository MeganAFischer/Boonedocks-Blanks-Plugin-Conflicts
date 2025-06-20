import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import Modal from '../../modal';
import Button from '../../button';
import fieldWithValidation from '../../field-with-validation';
import TextInputField from '../../form-fields/text-input-field';
import {
	isAlphabetic,
	isNonEmpty,
	isValuePlainText,
	Validator,
} from '../../../utils/validators';
import SideTabsField from '../../side-tabs-field';
import {
	CUSTOM_PROPERTY_STRUCTURE_NESTED,
	CUSTOM_PROPERTY_STRUCTURE_REPEATABLE,
	CUSTOM_PROPERTY_STRUCTURE_SIMPLE,
} from '../utils/property-utils';
import { showNotice } from '../utils/ui-utils';
import { createInterpolateElement } from '@wordpress/element';

const configLabelValidator = new Validator((value) => {
	return !value || !!value.match(/^[@.'_\-\sa-zA-Z0-9]+$/);
});

const PropertyLabelField = fieldWithValidation(TextInputField, [
	isValuePlainText,
	configLabelValidator,
]);
const PropertyNameField = fieldWithValidation(TextInputField, [
	isAlphabetic,
	isNonEmpty,
]);
const PropertyTypeField = fieldWithValidation(TextInputField, [
	isAlphabetic,
	isNonEmpty,
]);

export default class CustomSchemaPropertyAdditionModal extends React.Component {
	static defaultProps = {
		parentType: '',
		properties: {},
		onClose: () => false,
		onAdd: () => false,
	};

	constructor(props) {
		super(props);

		this.state = {
			label: '',
			labelValid: true,
			property: '',
			propertyValid: false,
			structure: CUSTOM_PROPERTY_STRUCTURE_SIMPLE,
			type: '',
			typeValid: true,
		};
	}

	render() {
		const { parentType, onClose } = this.props;
		const {
			label,
			labelValid,
			property,
			propertyValid,
			structure,
			type,
			typeValid,
		} = this.state;
		const submissionDisabled = !labelValid || !propertyValid || !typeValid;
		const onSubmit = () => this.handleSubmit();
		const schemaOrgUrl = parentType
			? 'https://schema.org/' + parentType
			: 'https://schema.org/docs/full.html';
		const description = createInterpolateElement(
			__(
				'Add a property to your custom schema type. You can find the list of supported properties on <a>schema.org</a>.',
				'wds'
			),
			{
				a: <a target="_blank" href={schemaOrgUrl} rel="noreferrer" />,
			}
		);

		const typeFieldMessageFormat =
			structure === 'nested'
				? // translators: %s: Property name.
				  __(
						'Enter the data type for the <strong>%s</strong> property from schema.org.',
						'wds'
				  )
				: // translators: %s: Property name.
				  __(
						'Enter the data type for the <strong>%s</strong> property that contains multiple nested items—for example, addresses for a business or reviews for a product.',
						'wds'
				  );
		const typeFieldMessage = sprintf(typeFieldMessageFormat, property);
		const typeFieldDescription = createInterpolateElement(
			typeFieldMessage,
			{ strong: <strong /> }
		);

		return (
			<Modal
				id="wds-custom-property-addition-modal"
				title={__('Add Property', 'wds')}
				description={description}
				small={true}
				onEnter={onSubmit}
				onClose={onClose}
				focusAfterOpen="wds-property-name"
				footer={
					<React.Fragment>
						<div className="sui-flex-child-right">
							<Button
								text={__('Cancel', 'wds')}
								ghost={true}
								onClick={onClose}
							/>
						</div>

						<div className="sui-actions-right">
							<Button
								text={__('Add', 'wds')}
								color="blue"
								disabled={submissionDisabled}
								onClick={onSubmit}
								icon="sui-icon-plus"
							/>
						</div>
					</React.Fragment>
				}
			>
				<PropertyNameField
					id="wds-property-name"
					label={__('Property', 'wds')}
					value={property}
					isRequired={true}
					onChange={(propertyName, isPropertyNameValid) =>
						this.handlePropertyChange(
							propertyName,
							isPropertyNameValid
						)
					}
					placeholder={__('E.g. jobLocation', 'wds')}
				/>

				<PropertyLabelField
					id="wds-property-label"
					label={__('Label (Optional)', 'wds')}
					value={label}
					onChange={(lb, lbValid) =>
						this.handleLabelChange(lb, lbValid)
					}
					placeholder={__('E.g. Job Location', 'wds')}
				/>

				<SideTabsField
					label={__('Structure', 'wds')}
					tabs={{
						[CUSTOM_PROPERTY_STRUCTURE_SIMPLE]: (
							<>
								<span className="wds-custom-icon-property-type-simple" />{' '}
								{__('Simple', 'wds')}
							</>
						),
						[CUSTOM_PROPERTY_STRUCTURE_NESTED]: (
							<>
								<span className="wds-custom-icon-property-type-nested" />{' '}
								{__('Nested', 'wds')}
							</>
						),
						[CUSTOM_PROPERTY_STRUCTURE_REPEATABLE]: (
							<>
								<span className="wds-custom-icon-property-type-collection" />{' '}
								{__('Collection', 'wds')}
							</>
						),
					}}
					value={structure}
					onChange={(struc) => this.handleStructureChange(struc)}
				>
					{(structure === 'nested' || structure === 'repeatable') && (
						<PropertyTypeField
							id="wds-property-type"
							label={__('Type', 'wds')}
							value={type}
							isRequired={true}
							onChange={(tp, isTypeValid) =>
								this.handleTypeChange(tp, isTypeValid)
							}
							description={typeFieldDescription}
							placeholder={__('E.g. Place', 'wds')}
						/>
					)}
				</SideTabsField>
			</Modal>
		);
	}

	handleSubmit() {
		const { properties, onAdd } = this.props;
		const { label, property, structure, type } = this.state;
		if (properties.hasOwnProperty(property)) {
			showNotice(__('That property already exists!', 'wds'), 'error');
		} else {
			onAdd(label, property, structure, type);
		}
	}

	handleLabelChange(value, isValid) {
		this.setState({
			label: value,
			labelValid: isValid,
		});
	}

	handlePropertyChange(value, isValid) {
		this.setState({
			property: value,
			propertyValid: isValid,
		});
	}

	handleStructureChange(value) {
		this.setState({
			structure: value,
			type: '',
			typeValid: value === 'simple',
		});
	}

	handleTypeChange(value, isValid) {
		this.setState({
			type: value,
			typeValid: isValid,
		});
	}
}
