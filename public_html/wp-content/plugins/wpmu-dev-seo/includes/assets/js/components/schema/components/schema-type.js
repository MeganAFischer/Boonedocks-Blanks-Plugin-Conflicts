import React from 'react';
import classnames from 'classnames';
import SchemaTypeAccordionHeader from './schema-type-accordion-header';
import SchemaTypeConditions from './schema-type-conditions';
import SchemaTypePropertiesTableContainer from './schema-type-properties-table';
import SchemaProperties from './schema-properties';
import connectTypeComponent from '../utils/connect-type-component';
import SchemaTypeDeletionModal from './schema-type-deletion-modal';
import SchemaTypeRenameModal from './schema-type-rename-modal';
import { __, sprintf } from '@wordpress/i18n';
import { showNotice } from '../utils/ui-utils';
import { propertiesExist } from '../utils/property-utils';
import { SchemaPropertiesNotFoundNotice } from './schema-properties-not-found-notice';

class SchemaType extends React.Component {
	static defaultProps = {
		typeId: '',
		label: '',
		disabled: false,
		conditions: [],
		properties: {},
		typeBlueprint: {},
		changeStatus: () => false,
		duplicateType: () => false,
		deleteType: () => false,
		renameType: () => false,
		addConditionGroup: () => false,
		updateCondition: () => false,
		addCondition: () => false,
		deleteCondition: () => false,
		toggleProperties: () => false,
	};

	constructor(props) {
		super(props);

		this.state = {
			isOpen: false,
			openedOnce: false,
			deletingType: false,
			renamingType: false,
		};
	}

	render() {
		const {
			typeId,
			label,
			disabled,
			conditions,
			properties,
			typeBlueprint,
			addConditionGroup,
			updateCondition,
			addCondition,
			deleteCondition,
			invalid,
		} = this.props;
		const { isOpen, openedOnce, deletingType, renamingType } = this.state;
		const isDisabled = disabled || typeBlueprint.disabled;
		const typeSubText = typeBlueprint.subText;

		return (
			<div
				className={classnames(
					'sui-accordion-item',
					'wds-schema-type-' + typeId + '-accordion',
					{
						'sui-accordion-item--open': isOpen,
						'sui-accordion-item--disabled': isDisabled,
					}
				)}
			>
				<SchemaTypeAccordionHeader
					label={label}
					icon={typeBlueprint.icon}
					isInvalid={invalid}
					disabled={disabled}
					conditions={conditions}
					onClick={() => this.toggleType()}
					onStatusChange={(status) => this.changeStatus(status)}
					onRename={() => this.startRenamingType()}
					onDuplicate={() => this.duplicateType()}
					onDelete={() => this.startDeletingType()}
				/>

				{deletingType && (
					<SchemaTypeDeletionModal
						onCancel={() => this.stopDeletingType()}
						onDelete={() => this.deleteType()}
					/>
				)}

				{renamingType && (
					<SchemaTypeRenameModal
						name={label}
						onRename={(newLabel) => this.renameType(newLabel)}
						onClose={() => this.stopRenamingType()}
					/>
				)}

				<div className="sui-accordion-item-body">
					{openedOnce && (
						<SchemaTypeConditions
							conditions={conditions}
							onAddGroup={addConditionGroup}
							onChange={updateCondition}
							onAdd={addCondition}
							onDelete={deleteCondition}
						/>
					)}

					{openedOnce && (
						<SchemaTypePropertiesTableContainer typeId={typeId}>
							{!propertiesExist(properties) && (
								<SchemaPropertiesNotFoundNotice />
							)}

							<SchemaProperties
								typeId={typeId}
								properties={properties}
							/>
						</SchemaTypePropertiesTableContainer>
					)}

					{typeSubText && (
						<span className="wds-type-sub-text">{typeSubText}</span>
					)}
				</div>
			</div>
		);
	}

	startRenamingType() {
		this.setState({
			renamingType: true,
		});
	}

	stopRenamingType() {
		this.setState({
			renamingType: false,
		});
	}

	startDeletingType() {
		this.setState({
			deletingType: true,
		});
	}

	stopDeletingType() {
		this.setState({
			deletingType: false,
		});
	}

	toggleType() {
		const { toggleProperties } = this.props;
		const { isOpen, openedOnce } = this.state;
		const nowOpen = !isOpen;

		if (!nowOpen) {
			toggleProperties(false);
		}

		this.setState({
			isOpen: nowOpen,
			openedOnce: nowOpen || openedOnce,
		});
	}

	renameType(newLabel) {
		this.props.renameType(newLabel);
		showNotice(__('The type has been renamed.', 'wds'));
		this.stopRenamingType();
	}

	deleteType() {
		this.props.deleteType();
		showNotice(
			__(
				'The type has been removed. You need to save the changes to make them live.',
				'wds'
			),
			'info'
		);
		this.stopDeletingType();
	}

	changeStatus(status) {
		const { label, changeStatus } = this.props;
		let message;
		if (status) {
			// translators: %s: Schema type name.
			message = __(
				'You have successfully activated the %s type.',
				'wds'
			);
		} else {
			// translators: %s: Schema type name.
			message = __(
				'You have successfully deactivated the %s type.',
				'wds'
			);
		}
		changeStatus(status);
		showNotice(sprintf(message, label));
	}

	duplicateType() {
		this.props.duplicateType();
		showNotice(__('The type has been duplicated successfully.', 'wds'));
	}
}

const SchemaTypeContainer = connectTypeComponent(SchemaType);
export default SchemaTypeContainer;
