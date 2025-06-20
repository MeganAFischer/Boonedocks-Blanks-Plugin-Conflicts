import React from 'react';
import Select from '../../input-fields/select';
import { __, sprintf } from '@wordpress/i18n';
import MediaItemSelector from '../../media-item-selector';
import Text from '../../input-fields/text';
import ConfigValues from '../../../es6/config-values';
import DatePicker from '../../date-picker';
import schemaSources from '../resources/sources/schema-sources';
import classnames from 'classnames';
import DurationSelector from '../../duration-selector';
import connectPropertyComponent from '../utils/connect-property-component';
import SchemaPropertyDeletionModal from './schema-property-deletion-modal';
import { showNotice } from '../utils/ui-utils';

class SchemaPropertySimple extends React.Component {
	static defaultProps = {
		typeId: '',
		id: '',
		label: '',
		description: '',
		source: '',
		value: '',
		disallowDeletion: false,
		required: false,
		updateProperty: () => false,
		deleteProperty: () => false,
	};

	constructor(props) {
		super(props);

		this.state = {
			deletingProperty: false,
		};
	}

	render() {
		const {
			id,
			label,
			description,
			source,
			value,
			required,
			requiredNotice,
			disallowDeletion,
		} = this.props;
		const { deletingProperty } = this.state;
		const sourceSelectOptions = this.getSourceSelectOptions();
		const valueElement = this.getValueElement(source, value);
		const requiredPropertyNotice = requiredNotice
			? requiredNotice
			: __('This property is required by Google.', 'wds');

		return (
			<tr className={'wds-schema-property-source-' + source}>
				<td className="sui-table-item-title wds-schema-property-label">
					<label className="sui-label sui-hidden-sm sui-hidden-md sui-hidden-lg">
						{__('Property', 'wds')}
					</label>
					<span
						className={classnames({
							'sui-tooltip sui-tooltip-constrained':
								!!description,
						})}
						style={{ '--tooltip-width': '300px' }}
						data-tooltip={description}
					>
						{label}
					</span>
					{required && (
						<span
							className="wds-required-asterisk sui-tooltip"
							data-tooltip={requiredPropertyNotice}
						>
							*
						</span>
					)}

					{!disallowDeletion && (
						<React.Fragment>
							<span
								className="sui-icon-trash sui-hidden-sm sui-hidden-md sui-hidden-lg"
								onClick={() => this.startDeletingProperty()}
								aria-hidden="true"
							/>
						</React.Fragment>
					)}

					{deletingProperty && (
						<SchemaPropertyDeletionModal
							requiredProperty={required}
							onCancel={() => this.stopDeletingProperty()}
							onDelete={() => this.deleteProperty()}
						/>
					)}
				</td>

				<td className="wds-schema-property-source">
					<label className="sui-label sui-hidden-sm sui-hidden-md sui-hidden-lg">
						{__('Source', 'wds')}
					</label>
					<Select
						key={sprintf('wds-property-%s-source', id)}
						options={sourceSelectOptions}
						small={true}
						selectedValue={source}
						onSelect={(src) => this.handleSourceChange(src)}
					/>
				</td>

				<td className="wds-schema-property-value">
					<label className="sui-label sui-hidden-sm sui-hidden-md sui-hidden-lg">
						{__('Value', 'wds')}
					</label>
					{valueElement}
				</td>

				<td className="wds-schema-property-delete">
					{!disallowDeletion && (
						<React.Fragment>
							<span
								className="sui-icon-trash"
								onClick={() => this.startDeletingProperty()}
								aria-hidden="true"
							/>
						</React.Fragment>
					)}
				</td>
			</tr>
		);
	}

	handleSourceChange(source) {
		const { id, updateProperty } = this.props;
		const valueOptions = this.getValueSelectOptions(source);
		let value = '';
		if (valueOptions) {
			value = Object.keys(valueOptions).shift();
		}

		updateProperty(id, source, value);
	}

	handleValueChange(value) {
		const { id, source, updateProperty } = this.props;

		updateProperty(id, source, value);
	}

	getSources() {
		const propertyType = this.getPropertyType();
		return Object.assign(
			{},
			this.getObjectValue(schemaSources, [propertyType, 'sources']),
			this.props.customSources || {}
		);
	}

	getSourceSelectOptions() {
		const sources = this.getSources();
		const options = {};
		Object.keys(sources).forEach((sourceKey) => {
			options[sourceKey] = sources[sourceKey].label;
		});

		return options;
	}

	getValueElement(source, value) {
		const key = `wds-property-${this.props.id}-source-${source}`;
		const selectOptions = this.getValueSelectOptions(source);

		if (selectOptions) {
			return (
				<Select
					key={key}
					options={selectOptions}
					multiple={this.props.allowMultipleSelection}
					small={true}
					onSelect={(selectValue) =>
						this.handleValueChange(selectValue)
					}
					selectedValue={value}
				/>
			);
		}

		if ('image' === source || 'image_url' === source) {
			return (
				<MediaItemSelector
					key={key}
					value={this.props.value}
					onChange={(imageId) => this.handleValueChange(imageId)}
				/>
			);
		}

		if ('custom_text' === source) {
			return (
				<Text
					key={key}
					value={this.props.value}
					placeholder={this.props.placeholder}
					adjustHeight={true}
					onChange={(text) => this.handleValueChange(text)}
				/>
			);
		}

		if ('post_meta' === source) {
			const ajaxURL = ConfigValues.get('ajax_url', 'schema_types');
			return (
				<Select
					key={key}
					tagging={true}
					placeholder={__('Search for meta key', 'wds')}
					options={{}}
					small={true}
					selectedValue={this.props.value}
					onSelect={(selectValue) =>
						this.handleValueChange(selectValue)
					}
					ajaxUrl={ajaxURL + '?action=wds-search-post-meta'}
				/>
			);
		}

		if ('datetime' === source) {
			return (
				<DatePicker
					value={this.props.value}
					onChange={(dateTimeValue) =>
						this.handleValueChange(dateTimeValue)
					}
				/>
			);
		}

		if ('number' === source) {
			return (
				<input
					type="number"
					value={this.props.value}
					onChange={(event) =>
						this.handleValueChange(event.target.value)
					}
				/>
			);
		}

		if ('duration' === source) {
			return (
				<DurationSelector
					value={this.props.value}
					onChange={(durationValue) =>
						this.handleValueChange(durationValue)
					}
				/>
			);
		}
	}

	getPropertyType() {
		return this.props.type;
	}

	getValueSelectOptions(sourceKey) {
		const options = this.getObjectValue(this.getSources(), [sourceKey]);
		if (options.hasOwnProperty('values')) {
			return options.values;
		}

		return false;
	}

	getObjectValue(object, keys) {
		let value = object;
		keys.forEach((key) => {
			value = value.hasOwnProperty(key) ? value[key] : [];
		});
		return value;
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
}

const SchemaPropertySimpleContainer =
	connectPropertyComponent(SchemaPropertySimple);
export default SchemaPropertySimpleContainer;
