import React from 'react';
import { first, last } from 'lodash-es';
import Modal from '../../modal';
import { __, sprintf } from '@wordpress/i18n';
import BoxSelector from '../../box-selector';
import Button from '../../button';
import SchemaTypeCondition from './schema-type-condition';
import SchemaTypeBlueprints from '../resources/schema-type-blueprints';
import Notice from '../../notices/notice';
import {
	addCondition,
	addConditionGroup,
	deleteCondition,
	getDefaultCondition,
	updateCondition,
} from '../utils/condition-utils';
import fieldWithValidation from '../../field-with-validation';
import TextInputField from '../../form-fields/text-input-field';
import { isValuePlainText } from '../../../utils/validators';

const TypeLabelField = fieldWithValidation(TextInputField, [isValuePlainText]);

export default class AddSchemaTypeWizardModal extends React.Component {
	static defaultProps = {
		onClose: () => false,
		onAdd: () => false,
	};

	constructor(props) {
		super(props);

		this.MODAL_STATE = {
			TYPE: 'type',
			LABEL: 'label',
			CONDITION: 'condition',
		};

		this.state = {
			modalState: this.MODAL_STATE.TYPE,
			selectedTypes: [],
			addedTypes: [],
			typeLabelValid: true,
			typeLabel: '',
			searchTerm: '',
			typeConditions: [],
		};
	}

	switchModalState(newModalState) {
		this.setState({
			modalState: newModalState,
		});
	}

	isStateType() {
		return this.state.modalState === this.MODAL_STATE.TYPE;
	}

	isStateLabel() {
		return this.state.modalState === this.MODAL_STATE.LABEL;
	}

	isStateCondition() {
		return this.state.modalState === this.MODAL_STATE.CONDITION;
	}

	switchToType() {
		this.switchModalState(this.MODAL_STATE.TYPE);
	}

	switchToLabel() {
		this.switchModalState(this.MODAL_STATE.LABEL);
	}

	switchToCondition() {
		this.switchModalState(this.MODAL_STATE.CONDITION);
	}

	clearSearchTerm() {
		this.setState({
			searchTerm: '',
		});
	}

	setSearchTerm(searchTerm) {
		this.setState({
			searchTerm,
		});
	}

	handleNextButtonClick() {
		if (this.isStateType()) {
			if (this.hasSubTypeOptions()) {
				this.loadSubTypes();
			} else {
				this.setNewLabel(this.getDefaultTypeLabel(), true);
				this.switchToLabel();
			}
		} else if (this.isStateLabel()) {
			this.setDefaultCondition(this.getTypeToAdd());
			this.switchToCondition();
		} else {
			this.addType();
		}
	}

	handleBackButtonClick() {
		this.clearSearchTerm();
		if (this.isStateType()) {
			if (this.typesAdded()) {
				this.loadPreviousTypes();
			} else {
				this.props.onClose();
			}
		} else if (this.isStateLabel()) {
			this.switchToType();
		} else {
			this.switchToLabel();
		}
	}

	getTypeToAdd() {
		let selected = false;
		if (this.state.selectedTypes.length) {
			selected = first(this.state.selectedTypes);
		} else if (this.typesAdded()) {
			selected = last(this.state.addedTypes);
		}

		return selected;
	}

	addType() {
		const defaultValue = this.getDefaultTypeLabel();

		this.props.onAdd(
			this.getTypeToAdd(),
			this.state.typeLabel.trim() || defaultValue,
			this.state.typeConditions
		);
	}

	loadSubTypes() {
		const selectedTypes = this.state.selectedTypes;
		const addedTypes = this.state.addedTypes.slice();

		addedTypes.push(first(selectedTypes));

		this.setState({
			selectedTypes: [],
			addedTypes,
		});
	}

	hasSubTypeOptions() {
		const selectedTypes = this.state.selectedTypes;
		if (!selectedTypes.length) {
			return false;
		}

		return !!this.getSubTypes(first(selectedTypes));
	}

	loadPreviousTypes() {
		const addedTypes = this.state.addedTypes.slice();
		const popped = addedTypes.pop();

		this.setState({
			selectedTypes: [popped],
			addedTypes,
		});
	}

	getOptions() {
		const addedTypes = this.state.addedTypes;
		let typeKeys;
		if (addedTypes.length) {
			typeKeys = this.getSubTypes(last(addedTypes));
			if (!typeKeys) {
				return [];
			}
		} else {
			typeKeys = SchemaTypeBlueprints.getTopLevelTypeKeys();
		}

		return this.buildOptionsFromTypes(typeKeys);
	}

	getSubTypes(typeKey) {
		const type = this.getTypeBlueprint(typeKey);
		return type.children.length ? type.children : false;
	}

	buildOptionsFromTypes(typeKeys) {
		const options = [];
		typeKeys.forEach((typeKey) => {
			const type = this.getTypeBlueprint(typeKey);
			if (
				!type.hidden &&
				(this.state.searchTerm.trim() === '' ||
					this.typeOrSubtypeMatchesSearch(typeKey))
			) {
				options.push({
					id: typeKey,
					label: type.label,
					icon: type.icon,
					disabled: !!type.disabled,
				});
			}
		});
		return options;
	}

	getTypeSection() {
		const options = this.getOptions();

		return (
			<React.Fragment>
				{this.breadcrumbs()}

				{this.typesAdded() && (
					<div id="wds-search-sub-types">
						<div className="sui-control-with-icon">
							<span
								className="sui-icon-magnifying-glass-search"
								aria-hidden="true"
							/>
							<input
								type="text"
								placeholder={__('Search subtypes', 'wds')}
								className="sui-form-control"
								value={this.state.searchTerm}
								onChange={(e) =>
									this.setSearchTerm(e.target.value)
								}
							/>
						</div>
					</div>
				)}

				<BoxSelector
					id="wds-add-schema-type-selector"
					options={options}
					selectedValues={this.state.selectedTypes}
					multiple={false}
					cols={3}
					onChange={(items) => this.handleSelection(items)}
				/>
			</React.Fragment>
		);
	}

	setNewLabel(label, labelValid) {
		this.setState({
			typeLabel: label,
			typeLabelValid: labelValid,
		});
	}

	getLabelSection() {
		const placeholder = this.getDefaultTypeLabel();
		const schemaReplacementNotice = this.getSchemaReplacementNotice();

		return (
			<div id="wds-add-schema-type-label">
				<TypeLabelField
					label={__('Type Name', 'wds')}
					value={this.state.typeLabel}
					onChange={(label, labelValid) =>
						this.setNewLabel(label, labelValid)
					}
					placeholder={placeholder}
				/>

				{schemaReplacementNotice && (
					<div style={{ marginTop: '20px', textAlign: 'left' }}>
						<Notice message={schemaReplacementNotice} type="info" />
					</div>
				)}
			</div>
		);
	}

	handleSelection(selectedTypes) {
		this.setState({ selectedTypes });
	}

	getSubTypesNotice(typeKey) {
		const type = this.getTypeBlueprint(typeKey);
		if (!type.children.length) {
			// No subtypes so no notice
			return '';
		}

		return type.subTypesNotice || '';
	}

	getSchemaReplacementNotice() {
		const typeToAdd = this.getTypeToAdd();
		if (!typeToAdd) {
			return false;
		}
		return this.getTypeBlueprint(typeToAdd).schemaReplacementNotice;
	}

	getTypeLabel(typeKey) {
		return this.getTypeBlueprint(typeKey).label;
	}

	getTypeLabelFull(typeKey) {
		const type = this.getTypeBlueprint(typeKey);

		return type.labelFull || type.label;
	}

	getTypeBlueprint(typeKey) {
		return SchemaTypeBlueprints.getTypeBlueprint(typeKey);
	}

	breadcrumbs() {
		const types = this.state.addedTypes.slice();
		const selectedTypes = this.state.selectedTypes;

		if (selectedTypes.length) {
			types.push(first(selectedTypes));
		}

		if (types.length) {
			return (
				<div id="wds-add-schema-type-breadcrumbs">
					{types.map((type) => (
						<span key={type}>
							{this.getTypeLabelFull(type)}
							<span
								className="sui-icon-chevron-right"
								aria-hidden="true"
							/>
						</span>
					))}
				</div>
			);
		}
	}

	isNextButtonDisabled() {
		if (this.isStateType()) {
			return !this.state.selectedTypes.length && !this.typesAdded();
		} else if (this.isStateLabel()) {
			return !this.state.typeLabelValid;
		}
		return false;
	}

	typesAdded() {
		return !!this.state.addedTypes.length;
	}

	getModalTitle() {
		if (this.isStateType() && this.typesAdded()) {
			return (
				<React.Fragment>
					<span className="sui-tag sui-tag-sm sui-tag-blue">
						{__('Optional', 'wds')}
					</span>
					<br />
					{__('Select Sub Type', 'wds')}
				</React.Fragment>
			);
		}
		return __('Add Schema Type', 'wds');
	}

	getModalDescription() {
		if (this.isStateType()) {
			if (this.typesAdded()) {
				const selected = last(this.state.addedTypes);
				return (
					<React.Fragment>
						{sprintf(
							/* translators: %s: Schema type label */
							__(
								'You can specify a subtype of %s, or you can skip this to add the generic type.',
								'wds'
							),
							this.getTypeLabel(selected)
						)}
						<br />
						{this.getSubTypesNotice(selected)}
					</React.Fragment>
				);
			}
			return __(
				'Start by selecting the schema type you want to use. By default, all of the types will include the properties required and recommended by Google.',
				'wds'
			);
		} else if (this.isStateLabel()) {
			return sprintf(
				// translators: %s: Schema type name.
				__(
					'Name your %s type so you can easily identify it.',
					'wds'
				),
				this.getDefaultTypeLabel()
			);
		}
		return __(
			'Create a set of rules to determine where this schema type will be enabled or excluded.',
			'wds'
		);
	}

	getDefaultTypeLabel() {
		return this.getTypeLabel(this.getTypeToAdd());
	}

	getConditionSection() {
		const conditions = this.state.typeConditions;
		const typeKey = this.getTypeToAdd();

		return (
			<div id="wds-add-schema-type-conditions">
				{this.getConditionGroupElements(typeKey, conditions)}

				<Button
					text={__('Add Rule (Or)', 'wds')}
					ghost={true}
					onClick={() => this.addGroup(typeKey)}
					icon="sui-icon-plus"
				/>
			</div>
		);
	}

	addGroup(typeKey) {
		const typeBlueprint = this.getTypeBlueprint(typeKey);

		this.setState({
			typeConditions: addConditionGroup(
				this.state.typeConditions,
				typeBlueprint
			),
		});
	}

	setDefaultCondition(type) {
		const typeBlueprint = this.getTypeBlueprint(type);
		const defaultCondition = getDefaultCondition([], typeBlueprint);

		this.setState({
			typeConditions: [[defaultCondition]],
		});
	}

	getConditionGroupElements(typeKey, conditions) {
		return conditions.map((conditionGroup, conditionGroupIndex) => {
			const firstCondition = first(conditionGroup);

			return (
				<div
					key={firstCondition.id}
					className="wds-schema-type-condition-group"
				>
					{conditionGroupIndex === 0 && (
						<span>{__('Rule', 'wds')}</span>
					)}
					{conditionGroupIndex !== 0 && (
						<span>{__('Or', 'wds')}</span>
					)}
					{this.getConditionElements(
						typeKey,
						conditionGroup,
						conditionGroupIndex
					)}
				</div>
			);
		});
	}

	getConditionElements(typeKey, conditionGroup, conditionGroupIndex) {
		return conditionGroup.map((condition, conditionIndex) => (
			<SchemaTypeCondition
				onChange={(id, lhs, operator, rhs) =>
					this.updateCondition(id, lhs, operator, rhs)
				}
				onAdd={(id) => this.addCondition(typeKey, id)}
				onDelete={(id) => this.deleteCondition(id)}
				disableDelete={
					conditionGroupIndex === 0 && conditionIndex === 0
				}
				key={condition.id}
				id={condition.id}
				lhs={condition.lhs}
				operator={condition.operator}
				rhs={condition.rhs}
			/>
		));
	}

	updateCondition(id, lhs, operator, rhs) {
		this.setState({
			typeConditions: updateCondition(
				this.state.typeConditions,
				id,
				lhs,
				operator,
				rhs
			),
		});
	}

	addCondition(typeKey, id) {
		const typeBlueprint = this.getTypeBlueprint(typeKey);
		this.setState({
			typeConditions: addCondition(
				this.state.typeConditions,
				id,
				typeBlueprint
			),
		});
	}

	deleteCondition(id) {
		this.setState({
			typeConditions: deleteCondition(this.state.typeConditions, id),
		});
	}

	stringIncludesSubstring(string, subString) {
		return string.toLowerCase().includes(subString.toLowerCase());
	}

	typeMatchesSearch(typeKey) {
		const typeMatches = this.stringIncludesSubstring(
			typeKey,
			this.state.searchTerm
		);
		if (typeMatches) {
			return true;
		}

		return this.stringIncludesSubstring(
			this.getTypeLabel(typeKey),
			this.state.searchTerm
		);
	}

	typeOrSubtypeMatchesSearch(typeKey) {
		if (this.typeMatchesSearch(typeKey)) {
			return true;
		}

		const subTypeKeys = this.getSubTypes(typeKey);

		if (!subTypeKeys) {
			return false;
		}
		let subtypeMatched = false;
		subTypeKeys.forEach((subTypeKey) => {
			if (this.typeMatchesSearch(subTypeKey)) {
				subtypeMatched = true;
			}
		});

		return subtypeMatched;
	}

	render() {
		return (
			<Modal
				id="wds-add-schema-type-modal"
				title={this.getModalTitle()}
				onClose={() => this.props.onClose()}
				small={true}
				dialogClasses={{
					'sui-modal-lg': true,
					'sui-modal-sm': false,
				}}
				description={this.getModalDescription()}
			>
				{this.isStateType() && this.getTypeSection()}
				{this.isStateLabel() && this.getLabelSection()}
				{this.isStateCondition() && this.getConditionSection()}

				<div
					style={{
						display: 'flex',
						justifyContent: 'space-between',
					}}
				>
					<Button
						text={__('Back', 'wds')}
						icon="sui-icon-arrow-left"
						id="wds-add-schema-type-back-button"
						onClick={() => this.handleBackButtonClick()}
						ghost={true}
					/>

					{!this.isStateCondition() && (
						<Button
							text={__('Continue', 'wds')}
							icon="sui-icon-arrow-right"
							id="wds-add-schema-type-action-button"
							onClick={() => this.handleNextButtonClick()}
							disabled={this.isNextButtonDisabled()}
						/>
					)}

					{this.isStateCondition() && (
						<Button
							text={__('Add', 'wds')}
							icon="sui-icon-plus"
							id="wds-add-schema-type-action-button"
							color="blue"
							onClick={() => this.handleNextButtonClick()}
						/>
					)}
				</div>
			</Modal>
		);
	}
}
