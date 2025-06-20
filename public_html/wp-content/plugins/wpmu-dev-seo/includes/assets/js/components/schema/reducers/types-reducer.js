import {
	ADD_TYPE,
	ADD_TYPE_CONDITION,
	ADD_TYPE_CONDITION_GROUP,
	ADD_TYPE_CUSTOM_PROPERTY,
	ADD_TYPE_PROPERTIES,
	CHANGE_TYPE_PROPERTY_VERSION,
	CHANGE_TYPE_STATUS,
	COPY_TYPE_CUSTOM_REPEATABLE_ITEM,
	DELETE_TYPE,
	DELETE_TYPE_CONDITION,
	DELETE_TYPE_PROPERTY,
	DUPLICATE_TYPE,
	RENAME_TYPE,
	REPEAT_TYPE_CUSTOM_PROPERTY,
	REPEAT_TYPE_PROPERTY,
	RESET_TYPE_PROPERTIES,
	TOGGLE_TYPE_PROPERTIES,
	TOGGLE_TYPE_PROPERTY,
	UPDATE_TYPE_CONDITION,
	UPDATE_TYPE_PROPERTY,
	VALIDATE_TYPE,
} from '../actions/types-actions';
import {
	addType,
	addTypeCondition,
	addTypeConditionGroup,
	addTypeCustomProperty,
	addTypeProperties,
	changeTypePropertyVersion,
	changeTypeStatus,
	copyTypeCustomRepeatableItem,
	deleteType,
	deleteTypeCondition,
	deleteTypeProperty,
	duplicateType,
	renameType,
	repeatTypeCustomProperty,
	repeatTypeProperty,
	resetTypeProperties,
	toggleTypeProperties,
	toggleTypeProperty,
	updateTypeCondition,
	updateTypeProperty,
	validate,
} from '../utils/type-utils';

export default function typesReducer(state = {}, action) {
	switch (action.type) {
		case ADD_TYPE:
			return addType(
				state,
				action.label,
				action.conditions,
				action.version,
				action.typeBlueprint
			);

		case DUPLICATE_TYPE:
			return duplicateType(state, action.typeId);

		case DELETE_TYPE:
			return deleteType(state, action.typeId);

		case RENAME_TYPE:
			return renameType(state, action.typeId, action.newLabel);

		case RESET_TYPE_PROPERTIES:
			return resetTypeProperties(
				state,
				action.typeId,
				action.typeBlueprint
			);

		case ADD_TYPE_PROPERTIES:
			return addTypeProperties(
				state,
				action.typeId,
				action.propertyBlueprintIds,
				action.typeBlueprint
			);

		case ADD_TYPE_CUSTOM_PROPERTY:
			return addTypeCustomProperty(
				state,
				action.typeId,
				action.propertyName,
				action.label,
				action.propertyType,
				action.structure,
				action.parentPropertyId
			);

		case UPDATE_TYPE_PROPERTY:
			return updateTypeProperty(
				state,
				action.typeId,
				action.propertyId,
				action.source,
				action.value
			);

		case DELETE_TYPE_PROPERTY:
			return deleteTypeProperty(state, action.typeId, action.propertyId);

		case REPEAT_TYPE_PROPERTY:
			return repeatTypeProperty(
				state,
				action.typeId,
				action.propertyId,
				action.typeBlueprint
			);

		case REPEAT_TYPE_CUSTOM_PROPERTY:
			return repeatTypeCustomProperty(
				state,
				action.typeId,
				action.propertyId
			);

		case COPY_TYPE_CUSTOM_REPEATABLE_ITEM:
			return copyTypeCustomRepeatableItem(
				state,
				action.typeId,
				action.repeatableItemId
			);

		case CHANGE_TYPE_PROPERTY_VERSION:
			return changeTypePropertyVersion(
				state,
				action.typeId,
				action.propertyId,
				action.newPropertyType,
				action.typeBlueprint
			);

		case ADD_TYPE_CONDITION_GROUP:
			return addTypeConditionGroup(
				state,
				action.typeId,
				action.typeBlueprint
			);

		case ADD_TYPE_CONDITION:
			return addTypeCondition(
				state,
				action.typeId,
				action.conditionId,
				action.typeBlueprint
			);

		case UPDATE_TYPE_CONDITION:
			return updateTypeCondition(
				state,
				action.typeId,
				action.conditionId,
				action.lhs,
				action.operator,
				action.rhs
			);

		case DELETE_TYPE_CONDITION:
			return deleteTypeCondition(
				state,
				action.typeId,
				action.conditionId
			);

		case CHANGE_TYPE_STATUS:
			return changeTypeStatus(state, action.typeId, action.status);

		case VALIDATE_TYPE:
			return validate(state, action.typeId);

		case TOGGLE_TYPE_PROPERTY:
			return toggleTypeProperty(
				state,
				action.typeId,
				action.propertyId,
				action.open
			);

		case TOGGLE_TYPE_PROPERTIES:
			return toggleTypeProperties(state, action.typeId, action.open);

		default:
			return state;
	}
}
