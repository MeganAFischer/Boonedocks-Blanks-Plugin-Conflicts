import { connect } from 'react-redux';
import SchemaTypeBlueprints from '../resources/schema-type-blueprints';
import {
	addTypeCondition,
	addTypeConditionGroup,
	addTypeCustomProperty,
	addTypeProperties,
	changeTypeStatus,
	deleteType,
	deleteTypeCondition,
	duplicateType,
	renameType,
	resetTypeProperties,
	toggleTypeProperties,
	toggleTypeProperty,
	updateTypeCondition,
	validate,
} from '../actions/types-actions';

const mapStateToProps = (state, { typeId }) => {
	const type = state.types[typeId];
	const typeBlueprint = SchemaTypeBlueprints.getTypeBlueprint(
		type.type,
		type.properties
	);

	return Object.assign({ typeBlueprint }, { ...type });
};
const mapDispatchToProps = (dispatch) => {
	return { dispatch };
};
const mergeProps = (stateProps, dispatchProps, ownProps) => {
	const typeId = ownProps.typeId;
	const typeBlueprint = stateProps.typeBlueprint;
	const dispatch = dispatchProps.dispatch;

	return {
		...stateProps,
		...ownProps,
		duplicateType: () => dispatch(duplicateType(typeId)),
		deleteType: () => dispatch(deleteType(typeId)),
		renameType: (newLabel) => dispatch(renameType(typeId, newLabel)),
		addProperties: (propertyBlueprintIds) => {
			dispatch(
				addTypeProperties(typeId, propertyBlueprintIds, typeBlueprint)
			);
			dispatch(validate(typeId));
		},
		resetProperties: () => {
			dispatch(resetTypeProperties(typeId, typeBlueprint));
			dispatch(validate(typeId));
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
		changeStatus: (status) => dispatch(changeTypeStatus(typeId, status)),
		addConditionGroup: () =>
			dispatch(addTypeConditionGroup(typeId, typeBlueprint)),
		updateCondition: (conditionId, lhs, operator, rhs) =>
			dispatch(
				updateTypeCondition(typeId, conditionId, lhs, operator, rhs)
			),
		addCondition: (conditionId) =>
			dispatch(addTypeCondition(typeId, conditionId, typeBlueprint)),
		deleteCondition: (conditionId) =>
			dispatch(deleteTypeCondition(typeId, conditionId)),
		toggleProperty: (id, open) =>
			dispatch(toggleTypeProperty(typeId, id, open)),
		toggleProperties: (open) =>
			dispatch(toggleTypeProperties(typeId, open)),
	};
};

const connectTypeComponent = (component) => {
	return connect(mapStateToProps, mapDispatchToProps, mergeProps)(component);
};

export default connectTypeComponent;
