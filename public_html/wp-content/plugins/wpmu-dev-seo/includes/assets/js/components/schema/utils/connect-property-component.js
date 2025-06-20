import {
	addTypeCustomProperty,
	addTypeProperties,
	changeTypePropertyVersion,
	copyTypeCustomRepeatableItem,
	deleteTypeProperty,
	repeatTypeCustomProperty,
	repeatTypeProperty,
	toggleTypeProperty,
	updateTypeProperty,
	validate,
} from '../actions/types-actions';
import SchemaTypeBlueprints from '../resources/schema-type-blueprints';
import {
	getBlueprintForProperty,
	getPropertyById,
	getPropertyName,
	getPropertyParent,
	isCustomProperty,
} from './property-utils';
import { batch, connect } from 'react-redux';

const mapStateToProps = (state, { typeId, id }) => {
	const type = state.types[typeId];
	const properties = type.properties;
	const typeBlueprint = SchemaTypeBlueprints.getTypeBlueprint(
		type.type,
		properties
	);
	const property = getPropertyById(properties, id);
	const propertyBlueprint = getBlueprintForProperty(
		properties,
		id,
		typeBlueprint.properties
	);

	return Object.assign(
		{
			typeLabel: type.label,
			blueprint: propertyBlueprint,
			parent: getPropertyParent(properties, id),
			propertyName: getPropertyName(properties, id),
			typeBlueprint,
		},
		{ ...property }
	);
};
const mapDispatchToProps = (dispatch) => {
	return { dispatch };
};
const mergeProps = (stateProps, dispatchProps, ownProps) => {
	const typeId = ownProps.typeId;
	const typeBlueprint = stateProps.typeBlueprint;
	const dispatch = dispatchProps.dispatch;
	const isPropertyCustom = isCustomProperty(stateProps);
	const repeatProperty = (id) => {
		batch(() => {
			dispatch(repeatTypeProperty(typeId, id, typeBlueprint));
			dispatch(validate(typeId));
		});
	};
	const repeatCustomProperty = (id) =>
		dispatch(repeatTypeCustomProperty(typeId, id));

	return {
		...stateProps,
		...ownProps,
		addProperties: (propertyBlueprintIds) => {
			batch(() => {
				dispatch(
					addTypeProperties(
						typeId,
						propertyBlueprintIds,
						typeBlueprint
					)
				);
				dispatch(validate(typeId));
			});
		},
		updateProperty: (id, source, value) => {
			batch(() => {
				dispatch(updateTypeProperty(typeId, id, source, value));
				dispatch(validate(typeId));
			});
		},
		deleteProperty: (id) => {
			batch(() => {
				dispatch(deleteTypeProperty(typeId, id));
				dispatch(validate(typeId));
			});
		},
		repeatProperty: isPropertyCustom
			? repeatCustomProperty
			: repeatProperty,
		changePropertyVersion: (id, newPropertyVersion) => {
			batch(() => {
				dispatch(
					changeTypePropertyVersion(
						typeId,
						id,
						newPropertyVersion,
						typeBlueprint
					)
				);
				dispatch(validate(typeId));
			});
		},
		addCustomProperty: (
			propertyName,
			label,
			propertyType,
			structure,
			parentPropertyId = false
		) => {
			dispatch(
				addTypeCustomProperty(
					typeId,
					propertyName,
					label,
					propertyType,
					structure,
					parentPropertyId
				)
			);
		},
		copyCustomRepeatableItem: (repeatableItemId) => {
			dispatch(copyTypeCustomRepeatableItem(typeId, repeatableItemId));
		},
		toggleProperty: (id, open) =>
			dispatch(toggleTypeProperty(typeId, id, open)),
	};
};
const connectPropertyComponent = (component) => {
	return connect(mapStateToProps, mapDispatchToProps, mergeProps)(component);
};
export default connectPropertyComponent;
