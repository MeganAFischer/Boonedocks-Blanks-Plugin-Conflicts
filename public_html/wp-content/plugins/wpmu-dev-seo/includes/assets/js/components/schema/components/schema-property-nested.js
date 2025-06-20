import React from 'react';
import classnames from 'classnames';
import Button from '../../button';
import SchemaProperties from './schema-properties';
import SchemaPropertyAccordionHeaderContainer from './schema-property-accordion-header';
import { __, _n, sprintf } from '@wordpress/i18n';
import SchemaPropertyAdditionModal from './schema-property-addition-modal';
import connectPropertyComponent from '../utils/connect-property-component';
import {
	findMissingPropertyKeys,
	getPropertyVersion,
	propertiesExist,
} from '../utils/property-utils';
import SchemaPropertyDeletionModal from './schema-property-deletion-modal';
import SchemaPropertyVersionChangeModal from './schema-property-version-change-modal';
import { showNotice } from '../utils/ui-utils';
import CustomSchemaPropertyAdditionModal from './custom-schema-property-addition-modal';
import { SchemaPropertiesNotFoundNotice } from './schema-properties-not-found-notice';

class SchemaPropertyNested extends React.Component {
	static defaultProps = {
		typeId: '',
		typeLabel: '',
		id: '',
		type: '', // The *property* type
		loop: false,
		loopDescription: '',
		disallowAddition: false,
		open: false,
		isCustom: false,
		properties: {},
		parent: {},
		blueprint: {},
		addProperties: () => false,
		changePropertyVersion: () => false,
		deleteProperty: () => false,
		toggleProperty: () => false,
	};

	constructor(props) {
		super(props);

		this.state = {
			addingNested: false,
			changingVersion: false,
			deletingProperty: false,
		};
	}

	render() {
		const {
			typeId,
			id,
			parent,
			loop,
			loopDescription,
			properties,
			disallowAddition,
			open,
		} = this.props;
		const { addingNested, changingVersion, deletingProperty } = this.state;
		const className = classnames(
			'sui-accordion-item',
			'wds-schema-property-' + id + '-accordion',
			{ 'sui-accordion-item--open': open }
		);
		const noProperties = !propertiesExist(properties);

		return (
			<tr key={'nested-property-row-' + id}>
				<td colSpan={4} className="wds-schema-nested-properties">
					<div className="sui-accordion">
						<div className={className}>
							<SchemaPropertyAccordionHeaderContainer
								typeId={typeId}
								id={id}
								onClick={() => this.toggle()}
								onChangeActiveVersion={() =>
									this.startChangingVersion()
								}
								onDelete={() => this.startDeletingProperty()}
							/>

							<div className="sui-accordion-item-body">
								{loop && <div>{loopDescription}</div>}

								<table className="sui-table">
									<tbody>
										<SchemaProperties
											typeId={typeId}
											properties={properties}
										/>

										{noProperties && (
											<SchemaPropertiesNotFoundNotice />
										)}
									</tbody>

									{!disallowAddition && (
										<tfoot>
											<tr>
												<td colSpan={4}>
													<Button
														onClick={() =>
															this.startAddingNested()
														}
														ghost={true}
														icon="sui-icon-plus"
														text={__(
															'Add Property',
															'wds'
														)}
													/>
												</td>
											</tr>
										</tfoot>
									)}
								</table>
							</div>

							{changingVersion && (
								<SchemaPropertyVersionChangeModal
									id={id}
									parent={parent}
									onChangePropertyVersion={(
										selectedVersion
									) => this.changeVersion(selectedVersion)}
									onClose={() => this.stopChangingVersion()}
								/>
							)}

							{deletingProperty !== false && (
								<SchemaPropertyDeletionModal
									onCancel={() => this.stopDeletingProperty()}
									onDelete={() => this.deleteProperty()}
								/>
							)}

							{addingNested && this.nestedPropertiesModal()}
						</div>
					</div>
				</td>
			</tr>
		);
	}

	componentDidUpdate(prevProps) {
		this.openNewlyAddedProperties(
			prevProps.properties,
			this.props.properties
		);
	}

	openNewlyAddedProperties(prevProperties, newProperties) {
		const { toggleProperty } = this.props;
		if (prevProperties === newProperties) {
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

	nestedPropertiesModal() {
		const { isCustom } = this.props;
		if (isCustom) {
			return this.customNestedPropertiesModal();
		}
		return this.presetNestedPropertiesModal();
	}

	customNestedPropertiesModal() {
		const { type, properties } = this.props;
		return (
			<CustomSchemaPropertyAdditionModal
				parentType={type}
				properties={properties}
				onClose={() => this.stopAddingNested()}
				onAdd={(label, propertyName, structure, tp) =>
					this.addCustomProperty(label, propertyName, structure, tp)
				}
			/>
		);
	}

	addCustomProperty(label, propertyName, structure, type) {
		const { id, addCustomProperty } = this.props;
		addCustomProperty(propertyName, label, type, structure, id);
		this.stopAddingNested();
	}

	presetNestedPropertiesModal() {
		const description = this.getNestedModalDescription();
		const options = this.getNestedModalOptions();

		return (
			<SchemaPropertyAdditionModal
				description={description}
				options={options}
				onClose={() => this.stopAddingNested()}
				onAction={(propertyIds) => this.addNested(propertyIds)}
			/>
		);
	}

	getNestedModalDescription() {
		const { blueprint, typeLabel } = this.props;

		return sprintf(
			// translators: 1: Blueprint label, 2: Type label.
			__(
				'Choose the properties to insert into the %1$s section of your %2$s schema.',
				'wds'
			),
			blueprint.label,
			typeLabel
		);
	}

	startAddingNested() {
		this.setState({ addingNested: true });
	}

	addNested(blueprintIds) {
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
		this.stopAddingNested();
	}

	stopAddingNested() {
		this.setState({ addingNested: false });
	}

	getNestedModalOptions() {
		const { properties, blueprint } = this.props;
		const blueprintProperties = blueprint.properties;
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

	toggle() {
		const { toggleProperty, id, open } = this.props;

		toggleProperty(id, !open);
	}

	startDeletingProperty() {
		this.setState({
			deletingProperty: true,
		});
	}

	deleteProperty() {
		const { id, deleteProperty } = this.props;
		deleteProperty(id);
		showNotice(
			__(
				'The property has been removed from this module.',
				'wds'
			),
			'info'
		);
		this.stopDeletingProperty();
	}

	stopDeletingProperty() {
		this.setState({
			deletingProperty: false,
		});
	}

	startChangingVersion() {
		this.setState({
			changingVersion: true,
		});
	}

	changeVersion(newVersion) {
		const { parent, changePropertyVersion, toggleProperty } = this.props;
		const newVersionProperty = getPropertyVersion(parent, newVersion);
		changePropertyVersion(parent.id, newVersion);
		toggleProperty(newVersionProperty.id, true);
		showNotice(
			sprintf(
				// translators: %s: version.
				__('Property type has been changed to %s', 'wds'),
				newVersion
			)
		);
		this.stopChangingVersion();
	}

	stopChangingVersion() {
		this.setState({
			changingVersion: false,
		});
	}
}

const SchemaPropertyNestedContainer =
	connectPropertyComponent(SchemaPropertyNested);
export default SchemaPropertyNestedContainer;
