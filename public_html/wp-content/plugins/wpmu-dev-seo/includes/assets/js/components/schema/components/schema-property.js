import React from 'react';
import SchemaProperties from './schema-properties';
import SchemaPropertySimpleContainer from './schema-property-simple';
import SchemaPropertyRepeatableContainer from './schema-property-repeatable';
import SchemaPropertyNestedContainer from './schema-property-nested';
import {
	getActivePropertyVersion,
	isFlattenedProperty,
	isNestedProperty,
	isRepeatableProperty,
	propertyHasAltVersions,
} from '../utils/property-utils';
import connectPropertyComponent from '../utils/connect-property-component';

class SchemaProperty extends React.Component {
	static defaultProps = {
		typeId: '', // ID of the type to which this property belongs
		id: '', // Unique identifier of the property
		label: '', // Human readable label
		description: '', // Human readable description
		type: '', // Data type of the value. Decides what will be shown under source and value
		source: '', // Data source e.g. 'Post' or 'Author'
		value: '', // The value to use from the data source e.g. 'Post Title' or 'Author Name'
		required: false, // Whether the current property is required by Google
		labelSingle: '', // Label of a single block in a repeatable
		disallowDeletion: false, // Whether to show a delete button
		disallowAddition: false, // Whether to show a button for adding nested properties
		customSources: {}, // Custom source options for this property only
		placeholder: '', // Text input placeholder
		disallowFirstItemDeletionOnly: false, // If true a delete button is not shown for the first item but is shown on all subsequent items
		loop: false, // Identifier of the loop
		loopDescription: '', // Description of the loop
		requiredNotice: '', // Shown as a tooltip of '*'
		requiredInBlock: false, // Deprecated.
		allowMultipleSelection: false, // Allow selection of multiple values in a select field
		isAnAltVersion: false, // Indicates whether this property is an alternate version of some other property
		activeVersion: false, // Which of the available alternate versions is currently active
		flatten: false, // Shows child properties without nesting
		isCustom: false, // Is this a custom property?
		properties: false, // Nested properties/available alt versions
	};

	render() {
		const { typeId, id } = this.props;
		const property = this.props;

		if (propertyHasAltVersions(property)) {
			const activeVersion = getActivePropertyVersion(property);
			return (
				<SchemaPropertyContainer
					typeId={typeId}
					id={activeVersion.id}
				/>
			);
		} else if (isFlattenedProperty(property)) {
			return (
				<SchemaProperties
					typeId={typeId}
					properties={property.properties}
				/>
			);
		} else if (isRepeatableProperty(property)) {
			return (
				<SchemaPropertyRepeatableContainer typeId={typeId} id={id} />
			);
		} else if (isNestedProperty(property)) {
			return <SchemaPropertyNestedContainer typeId={typeId} id={id} />;
		}
		return <SchemaPropertySimpleContainer typeId={typeId} id={id} />;
	}
}

const SchemaPropertyContainer = connectPropertyComponent(SchemaProperty);
export default SchemaPropertyContainer;
