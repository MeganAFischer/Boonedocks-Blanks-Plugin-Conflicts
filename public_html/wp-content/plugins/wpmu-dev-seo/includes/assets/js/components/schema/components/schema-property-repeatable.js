import React from 'react';
import classnames from 'classnames';
import Button from '../../button';
import SchemaProperties from './schema-properties';
import SchemaPropertySimple from './schema-property-simple';
import SchemaPropertyAccordionHeaderContainer from './schema-property-accordion-header';
import connectPropertyComponent from '../utils/connect-property-component';
import SchemaPropertyDeletionModal from './schema-property-deletion-modal';
import SchemaPropertyVersionChangeModal from './schema-property-version-change-modal';
import {
	calculateLabelSingle,
	getPropertyVersion,
	isNestedProperty,
	propertiesExist,
} from '../utils/property-utils';
import { showNotice } from '../utils/ui-utils';
import { __, sprintf } from '@wordpress/i18n';
import CustomSchemaPropertyAdditionModal from './custom-schema-property-addition-modal';
import { SchemaPropertiesNotFoundNotice } from './schema-properties-not-found-notice';

class SchemaPropertyRepeatable extends React.Component {
	static defaultProps = {
		typeId: '',
		id: '',
		label: '',
		labelSingle: '',
		propertyName: '',
		open: false,
		parent: {},
		properties: {},
		changePropertyVersion: () => false,
		repeatProperty: () => false,
		deleteProperty: () => false,
		toggleProperty: () => false,
		addCustomProperty: () => false,
		copyCustomRepeatableItem: () => false,
	};

	constructor(props) {
		super(props);

		this.state = {
			addingNested: false,
			changingVersion: false,
			deletingProperty: false,
		};

		this.labelSingleCalculated = calculateLabelSingle(
			this.props.propertyName
		);
	}

	render() {
		const { typeId, id, open, parent, properties } = this.props;
		const { addingNested, changingVersion, deletingProperty } = this.state;
		const repeatables = properties;

		return (
			<tr key={'repeating-property-row-' + id}>
				<td colSpan={4} className="wds-schema-repeating-properties">
					<div className="sui-accordion">
						<div
							className={classnames(
								'sui-accordion-item',
								'wds-schema-property-' + id + '-accordion',
								{ 'sui-accordion-item--open': open }
							)}
						>
							<SchemaPropertyAccordionHeaderContainer
								typeId={typeId}
								id={id}
								onClick={() => this.toggle()}
								isRepeatable={true}
								onRepeat={() => this.repeatProperty()}
								onChangeActiveVersion={() =>
									this.startChangingVersion()
								}
								onDelete={() => this.startDeletingProperty(id)}
							/>

							<div className="sui-accordion-item-body">
								{Object.keys(repeatables).map((propertyKey) => {
									const repeatable = repeatables[propertyKey];
									return (
										<table
											className="sui-table"
											key={'repeatable-' + repeatable.id}
										>
											{repeatable.properties && (
												<thead>
													<tr>
														<td
															colSpan={4}
															className="wds-schema-repeatable-title sui-table-item-title"
														>
															<div className="wds-schema-repeatable-title-inner">
																<div>
																	<span>
																		{this.getLabelSingle()}
																	</span>
																	{repeatable.isCustom &&
																		!!repeatable.type && (
																			<span className="sui-tag sui-tag-sm">
																				{
																					repeatable.type
																				}
																			</span>
																		)}
																</div>

																<div className="wds-schema-repeatable-button-controls">
																	{repeatable.isCustom && (
																		<span
																			className="sui-tooltip"
																			style={{
																				display:
																					'inline-block',
																			}}
																			data-tooltip={sprintf(
																				// translators: %s: Property name.
																				__(
																					'Copy this %s',
																					'wds'
																				),
																				this.getLabelSingle()
																			)}
																		>
																			<Button
																				text=""
																				className="wds-schema-repeatable-copy"
																				ghost={
																					true
																				}
																				icon="sui-icon-copy"
																				onClick={() =>
																					this.copyCustomRepeatableItem(
																						repeatable.id
																					)
																				}
																			/>
																		</span>
																	)}

																	{!repeatable.disallowDeletion && (
																		<span
																			className="sui-tooltip"
																			style={{
																				display:
																					'inline-block',
																			}}
																			data-tooltip={sprintf(
																				// translators: %s: Property name.
																				__(
																					'Delete this %s',
																					'wds'
																				),
																				this.getLabelSingle()
																			)}
																		>
																			<Button
																				text=""
																				className="wds-schema-repeatable-delete"
																				ghost={
																					true
																				}
																				icon="sui-icon-trash"
																				color="red"
																				onClick={() =>
																					this.startDeletingProperty(
																						repeatable.id
																					)
																				}
																			/>
																		</span>
																	)}
																</div>
															</div>
														</td>
													</tr>
												</thead>
											)}

											<tbody>
												{isNestedProperty(
													repeatable
												) && (
													<SchemaProperties
														typeId={typeId}
														properties={
															repeatable.properties
														}
													/>
												)}

												{isNestedProperty(repeatable) &&
													!propertiesExist(
														repeatable.properties
													) && (
														<SchemaPropertiesNotFoundNotice />
													)}

												{!isNestedProperty(
													repeatable
												) && (
													<SchemaPropertySimple
														typeId={typeId}
														id={repeatable.id}
													/>
												)}
											</tbody>

											{repeatable.isCustom && (
												<tfoot>
													<tr>
														<td colSpan={4}>
															<Button
																onClick={() =>
																	this.startAddingNested(
																		repeatable.id
																	)
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
									);
								})}
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
									onDelete={() =>
										this.deleteProperty(deletingProperty)
									}
								/>
							)}

							{addingNested !== false &&
								this.customSchemaPropertyAdditionModal()}
						</div>
					</div>
				</td>
			</tr>
		);
	}

	customSchemaPropertyAdditionModal() {
		const repeatables = this.props.properties;
		const parentPropertyId = this.state.addingNested;
		const repeatableKey = Object.keys(repeatables).find(
			(rptKey) => repeatables[rptKey].id === parentPropertyId
		);

		if (
			!repeatables.hasOwnProperty(repeatableKey) ||
			!isNestedProperty(repeatables[repeatableKey])
		) {
			return false;
		}

		return (
			<CustomSchemaPropertyAdditionModal
				parentType={repeatables[repeatableKey].type}
				properties={repeatables[repeatableKey].properties}
				onClose={() => this.stopAddingNested()}
				onAdd={(label, propertyName, structure, type) =>
					this.addCustomProperty(
						label,
						propertyName,
						structure,
						type,
						parentPropertyId
					)
				}
			/>
		);
	}

	toggle() {
		const { toggleProperty, id, open } = this.props;

		toggleProperty(id, !open);
	}

	startDeletingProperty(id) {
		this.setState({
			deletingProperty: id,
		});
	}

	deleteProperty(id) {
		const { deleteProperty } = this.props;
		deleteProperty(id);
		showNotice(
			sprintf(
				// translators: %s: Property name.
				__('The %s has been removed.', 'wds'),
				this.getLabelSingle()
			)
		);
		this.stopDeletingProperty();
	}

	stopDeletingProperty() {
		this.setState({
			deletingProperty: false,
		});
	}

	repeatProperty() {
		const { id, repeatProperty, toggleProperty } = this.props;
		toggleProperty(id, true);
		repeatProperty(id);
		showNotice(
			sprintf(
				// translators: %s: Property name.
				__('A new %s has been added.', 'wds'),
				this.getLabelSingle()
			)
		);
	}

	getLabelSingle() {
		const { label, labelSingle } = this.props;
		const labelSingleCalculated = this.labelSingleCalculated;
		return labelSingle || labelSingleCalculated || label;
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
				// translators: %s: Property type name.
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

	startAddingNested(parentPropertyId) {
		this.setState({ addingNested: parentPropertyId });
	}

	addCustomProperty(label, propertyName, structure, type, parentPropertyId) {
		const { addCustomProperty } = this.props;
		addCustomProperty(
			propertyName,
			label,
			type,
			structure,
			parentPropertyId
		);
		this.stopAddingNested();
	}

	stopAddingNested() {
		this.setState({ addingNested: false });
	}

	copyCustomRepeatableItem(repeatableItemId) {
		const { copyCustomRepeatableItem } = this.props;
		copyCustomRepeatableItem(repeatableItemId);
		showNotice(
			sprintf(
				// translators: %s: Property name.
				__('The %s has been copied.', 'wds'),
				this.getLabelSingle()
			),
			'info'
		);
	}
}

const SchemaPropertyRepeatableContainer = connectPropertyComponent(
	SchemaPropertyRepeatable
);
export default SchemaPropertyRepeatableContainer;
