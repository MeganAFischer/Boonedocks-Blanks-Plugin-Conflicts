import React from 'react';
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import SchemaTypeContainer from './schema-type';
import Button from '../../button';
import SchemaTypesBoxFooter from './schema-types-box-footer';
import AddSchemaTypeWizardModal from './add-schema-type-wizard-modal';
import { connect } from 'react-redux';
import SchemaTypeBlueprints from '../resources/schema-type-blueprints';
import ConfigValues from '../../../es6/config-values';
import { addType } from '../actions/types-actions';
import Notice from '../../notices/notice';
import { createInterpolateElement } from '@wordpress/element';
import { typesValid } from '../utils/type-utils';
import NoticeUtil from '../../../utils/notice-util';
import { showNotice } from '../utils/ui-utils';
import UrlUtil from '../../../utils/url-util';

class SchemaTypesBuilder extends React.Component {
	static defaultProps = {
		types: {},
		addType: () => false,
	};

	constructor(props) {
		super(props);

		this.state = {
			addingSchemaType: false,
			valid: typesValid(this.props.types),
		};
	}

	componentDidMount() {
		this.maybeStartAddingSchemaType();
	}

	componentDidUpdate(prevProps) {
		const valid = typesValid(this.props.types);
		this.maybeUpdateValidity(valid).then(() => {
			this.toggleInvalidTypesFloatingNotice(!valid);
		});

		this.maybeShowAfterAdditionNotice(prevProps.types);
	}

	maybeUpdateValidity(valid) {
		return new Promise((resolve) => {
			if (this.state.valid !== valid) {
				this.setState(
					{
						valid,
					},
					resolve
				);
			}
		});
	}

	maybeShowAfterAdditionNotice(prevTypes) {
		const { types } = this.props;
		const newTypeIds = Object.keys(types);
		const prevTypeIds = Object.keys(prevTypes);
		if (newTypeIds.length === prevTypeIds.length) {
			return;
		}
		const addedTypeIds = newTypeIds.filter(
			(typeId) => !prevTypeIds.includes(typeId)
		);
		let counter = 1;
		addedTypeIds.forEach((addedTypeId) => {
			const typeBlueprint = SchemaTypeBlueprints.getTypeBlueprint(
				types[addedTypeId].type
			);
			if (typeBlueprint.afterAdditionNotice) {
				setTimeout(() => {
					NoticeUtil.showNotice(
						'wds-schema-types-after-addition-notice',
						typeBlueprint.afterAdditionNotice,
						'info',
						true
					);
				}, 500 * counter++);
			}
		});
	}

	render() {
		const { types } = this.props;
		const { addingSchemaType, valid } = this.state;
		const typeIds = Object.keys(types);

		return (
			<React.Fragment>
				{!valid && this.invalidTypesNotice()}

				<div
					id="wds-schema-types-body"
					className={classnames({
						hidden: !typeIds.length,
					})}
				>
					<div className="sui-row">
						<div className="sui-col-xs-6 sui-col-sm-5">
							<small>
								<strong>{__('Schema Type', 'wds')}</strong>
							</small>
						</div>
						<div className="sui-col-xs-6 sui-col-sm-7">
							<small>
								<strong>{__('Location', 'wds')}</strong>
							</small>
						</div>
					</div>

					<div className="sui-accordion sui-accordion-flushed">
						{typeIds.map((typeId) => (
							<SchemaTypeContainer key={typeId} typeId={typeId} />
						))}
					</div>
				</div>

				<div id="wds-schema-types-footer">
					<Button
						onClick={() => this.startAddingSchemaType()}
						dashed={true}
						icon="sui-icon-plus"
						text={__('Add New Type', 'wds')}
					/>

					<p className="sui-description">
						{__(
							'Add additional schema types you want to output on this site.',
							'wds'
						)}
					</p>

					<SchemaTypesBoxFooter />
				</div>

				{addingSchemaType && (
					<AddSchemaTypeWizardModal
						onClose={() => this.stopAddingSchemaType()}
						onAdd={(type, label, conditions) =>
							this.handleTypeAddition(type, label, conditions)
						}
					/>
				)}

				<input
					type="hidden"
					name="wds-schema-types"
					value={JSON.stringify(types)}
				/>
			</React.Fragment>
		);
	}

	toggleInvalidTypesFloatingNotice(show) {
		const id = 'wds-schema-types-invalid-notice';

		if (show) {
			const message = __(
				'One or more properties that are required by Google have been removed. Please check your types and click on the <strong>Add Property</strong> button to see the missing <strong>required properties</strong> ( <span>*</span> ).'
			);
			// We have to do a setTimeout because the "property deleted" notice interferes with the current notice.
			setTimeout(() => {
				NoticeUtil.showWarningNotice(id, message);
			}, 500);
		} else {
			NoticeUtil.closeNotice(id);
		}
	}

	invalidTypesNotice() {
		return (
			<Notice
				message={createInterpolateElement(
					__(
						'One or more types have properties that are required by Google that have been removed. Please check your types and click on the <strong>Add Property</strong> button to add the missing <strong>required properties</strong> ( <span>*</span> ), for your content to be eligible for display as a rich result. To learn more about schema type properties, see our <DocLink>Schema Documentation</DocLink>.'
					),
					{
						strong: <strong />,
						span: <span />,
						DocLink: (
							<a
								target="_blank"
								href="https://wpmudev.com/docs/wpmu-dev-plugins/smartcrawl/#schema"
								rel="noreferrer"
							/>
						),
					}
				)}
			/>
		);
	}

	handleTypeAddition(type, label, conditions) {
		// eslint-disable-next-line no-shadow
		const { addType } = this.props;

		addType(type, label, conditions);
		showNotice(
			__(
				'The type has been added. You need to save the changes to make them live.',
				'wds'
			)
		);
		this.stopAddingSchemaType();
	}

	startAddingSchemaType() {
		this.setState({
			addingSchemaType: true,
		});
	}

	stopAddingSchemaType() {
		this.setState({
			addingSchemaType: false,
		});

		this.removeAddTypeQueryVar();
	}

	maybeStartAddingSchemaType() {
		if (UrlUtil.getQueryParam('add_type') === '1') {
			this.startAddingSchemaType();
		}
	}

	removeAddTypeQueryVar() {
		UrlUtil.removeQueryParam('add_type');
	}
}

const mapStateToProps = (state) => {
	return {
		types: state.types,
	};
};
const mapDispatchToProps = (dispatch) => {
	const version = ConfigValues.get('plugin_version', 'schema_types');

	return {
		addType: (type, label, conditions) =>
			dispatch(
				addType(
					label,
					conditions,
					version,
					SchemaTypeBlueprints.getTypeBlueprint(type)
				)
			),
	};
};
const SchemaTypesBuilderContainer = connect(
	mapStateToProps,
	mapDispatchToProps
)(SchemaTypesBuilder);

export default SchemaTypesBuilderContainer;
