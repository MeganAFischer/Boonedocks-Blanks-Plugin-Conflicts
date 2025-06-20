import React from 'react';
import { __, _n, sprintf } from '@wordpress/i18n';
import Button from '../../button';
import SchemaTypeResetModal from './schema-type-reset-modal';
import SchemaPropertyAdditionModal from './schema-property-addition-modal';
import { findMissingPropertyKeys } from '../utils/property-utils';
import connectTypeComponent from '../utils/connect-type-component';
import { showNotice } from '../utils/ui-utils';
import CustomSchemaPropertyAdditionModal from './custom-schema-property-addition-modal';
import { isCustomType } from '../utils/type-utils';

class SchemaTypePropertiesTable extends React.Component {
	static defaultProps = {
		typeId: '',
		label: '',
		type: '',
		properties: '',
		typeBlueprint: {},
		addProperties: () => false,
		addCustomProperty: () => false,
		addCustomNestedProperty: () => false,
		resetProperties: () => false,
		toggleProperty: () => false,
	};

	constructor(props) {
		super(props);

		this.state = {
			resetting: false,
			adding: false,
		};
	}

	componentDidUpdate(prevProps) {
		this.openNewlyAddedProperties(
			prevProps.properties,
			this.props.properties
		);
	}

	openNewlyAddedProperties(prevProperties, newProperties) {
		const { toggleProperty } = this.props;
		if (
			Object.keys(prevProperties).length ===
			Object.keys(newProperties).length
		) {
			return;
		}
		const addedPropertyKeys = findMissingPropertyKeys(
			prevProperties,
			newProperties
		);
		if (addedPropertyKeys.length) {
			addedPropertyKeys.forEach((addedPropertyKey) => {
				toggleProperty(newProperties[addedPropertyKey].id, true);
			});
		}
	}

	render() {
		const { type } = this.props;
		const { resetting, adding } = this.state;
		const isTypeCustom = isCustomType(type);

		return (
			<table className="sui-table wds-schema-type-property-table">
				<thead className="sui-hidden-xs">
					<tr>
						<th>{__('Property', 'wds')}</th>
						<th>{__('Source', 'wds')}</th>
						<th colSpan={2}>{__('Value', 'wds')}</th>
					</tr>
				</thead>

				<tbody>{this.props.children}</tbody>

				<tfoot>
					<tr>
						<td colSpan={4}>
							<div className="schema-type-footer-controls">
								{resetting && (
									<SchemaTypeResetModal
										onCancel={() => this.stopResetting()}
										onReset={() => this.resetProperties()}
									/>
								)}

								{adding && this.addPropertiesModal()}

								<React.Fragment>
									{!isTypeCustom && (
										<Button
											ghost={true}
											onClick={() =>
												this.startResetting()
											}
											icon="sui-icon-refresh"
											text={__(
												'Reset Properties',
												'wds'
											)}
											tooltip={__(
												'Reset the properties list to default.',
												'wds'
											)}
										/>
									)}

									<Button
										icon="sui-icon-plus"
										ghost={true}
										onClick={() =>
											this.startAddingProperty()
										}
										text={__('Add Property', 'wds')}
									/>
								</React.Fragment>
							</div>
						</td>
					</tr>
				</tfoot>
			</table>
		);
	}

	startResetting() {
		this.setState({
			resetting: true,
		});
	}

	resetProperties() {
		this.props.resetProperties();
		showNotice(__('Properties have been reset to default', 'wds'));
		this.stopResetting();
	}

	stopResetting() {
		this.setState({
			resetting: false,
		});
	}

	startAddingProperty() {
		this.setState({
			adding: true,
		});
	}

	stopAddingProperty() {
		this.setState({
			adding: false,
		});
	}

	addProperties(blueprintIds) {
		const { addProperties } = this.props;
		addProperties(blueprintIds);
		showNotice(
			_n(
				'The property has been added. You need to save the changes to make them live.',
				'The properties have been added. You need to save the changes to make them live.',
				blueprintIds.length,
				'wds'
			)
		);
		this.stopAddingProperty();
	}

	addPropertiesModal() {
		const { type } = this.props;
		const isTypeCustom = isCustomType(type);

		if (isTypeCustom) {
			return this.addCustomPropertyModal();
		}
		return this.addPresetPropertiesModal();
	}

	addCustomPropertyModal() {
		const { properties } = this.props;
		return (
			<CustomSchemaPropertyAdditionModal
				properties={properties}
				onClose={() => this.stopAddingProperty()}
				onAdd={(label, propertyName, structure, type) =>
					this.addCustomProperty(label, propertyName, structure, type)
				}
			/>
		);
	}

	addCustomProperty(label, propertyName, structure, type) {
		const { addCustomProperty } = this.props;
		addCustomProperty(propertyName, label, type, structure);
		this.stopAddingProperty();
	}

	addPresetPropertiesModal() {
		const { label } = this.props;
		const description = sprintf(
			// translators: %s: Property name.
			__(
				'Choose the properties to insert into your %s type module.',
				'wds'
			),
			label
		);

		return (
			<SchemaPropertyAdditionModal
				description={description}
				options={this.getPropertiesModalOptions()}
				onClose={() => this.stopAddingProperty()}
				onAction={(propertyIds) => this.addProperties(propertyIds)}
			/>
		);
	}

	getPropertiesModalOptions() {
		const { properties, typeBlueprint } = this.props;
		const blueprintProperties = typeBlueprint.properties;
		const missingPropertyKeys = findMissingPropertyKeys(
			properties,
			blueprintProperties
		);
		return missingPropertyKeys.map((key) => ({
			id: blueprintProperties[key].id,
			label: blueprintProperties[key].label,
			required: blueprintProperties[key].required,
		}));
	}
}

const SchemaTypePropertiesTableContainer = connectTypeComponent(
	SchemaTypePropertiesTable
);
export default SchemaTypePropertiesTableContainer;
