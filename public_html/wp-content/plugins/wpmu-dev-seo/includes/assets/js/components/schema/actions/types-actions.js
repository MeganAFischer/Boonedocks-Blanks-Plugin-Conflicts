export const ADD_TYPE = 'ADD_TYPE';
export const DUPLICATE_TYPE = 'DUPLICATE_TYPE';
export const DELETE_TYPE = 'DELETE_TYPE';
export const RENAME_TYPE = 'RENAME_TYPE';
export const RESET_TYPE_PROPERTIES = 'RESET_TYPE_PROPERTIES';
export const ADD_TYPE_PROPERTIES = 'ADD_TYPE_PROPERTIES';
export const ADD_TYPE_CUSTOM_PROPERTY = 'ADD_TYPE_CUSTOM_PROPERTY';
export const UPDATE_TYPE_PROPERTY = 'UPDATE_TYPE_PROPERTY';
export const REPEAT_TYPE_PROPERTY = 'REPEAT_TYPE_PROPERTY';
export const REPEAT_TYPE_CUSTOM_PROPERTY = 'REPEAT_TYPE_CUSTOM_PROPERTY';
export const COPY_TYPE_CUSTOM_REPEATABLE_ITEM =
	'COPY_TYPE_CUSTOM_REPEATABLE_ITEM';
export const CHANGE_TYPE_PROPERTY_VERSION = 'CHANGE_TYPE_PROPERTY_VERSION';
export const DELETE_TYPE_PROPERTY = 'DELETE_TYPE_PROPERTY';
export const ADD_TYPE_CONDITION = 'ADD_TYPE_CONDITION';
export const ADD_TYPE_CONDITION_GROUP = 'ADD_TYPE_CONDITION_GROUP';
export const UPDATE_TYPE_CONDITION = 'UPDATE_TYPE_CONDITION';
export const DELETE_TYPE_CONDITION = 'DELETE_TYPE_CONDITION';
export const CHANGE_TYPE_STATUS = 'CHANGE_TYPE_STATUS';
export const VALIDATE_TYPE = 'VALIDATE_TYPE';
export const TOGGLE_TYPE_PROPERTY = 'TOGGLE_TYPE_PROPERTY';
export const TOGGLE_TYPE_PROPERTIES = 'TOGGLE_TYPE_PROPERTIES';

export const addType = (label, conditions, version, typeBlueprint) => ({
	type: ADD_TYPE,
	label,
	conditions,
	version,
	typeBlueprint,
});

export const duplicateType = (typeId) => ({
	type: DUPLICATE_TYPE,
	typeId,
});

export const deleteType = (typeId) => ({
	type: DELETE_TYPE,
	typeId,
});

export const renameType = (typeId, newLabel) => ({
	type: RENAME_TYPE,
	typeId,
	newLabel,
});

export const resetTypeProperties = (typeId, typeBlueprint) => ({
	type: RESET_TYPE_PROPERTIES,
	typeId,
	typeBlueprint,
});

export const addTypeProperties = (
	typeId,
	propertyBlueprintIds,
	typeBlueprint
) => ({
	type: ADD_TYPE_PROPERTIES,
	typeId,
	propertyBlueprintIds,
	typeBlueprint,
});

export const addTypeCustomProperty = (
	typeId,
	propertyName,
	label,
	propertyType,
	structure,
	parentPropertyId
) => ({
	type: ADD_TYPE_CUSTOM_PROPERTY,
	typeId,
	propertyName,
	label,
	propertyType,
	structure,
	parentPropertyId,
});

export const updateTypeProperty = (typeId, propertyId, source, value) => ({
	type: UPDATE_TYPE_PROPERTY,
	typeId,
	propertyId,
	source,
	value,
});

export const deleteTypeProperty = (typeId, propertyId) => ({
	type: DELETE_TYPE_PROPERTY,
	typeId,
	propertyId,
});

export const repeatTypeProperty = (typeId, propertyId, typeBlueprint) => ({
	type: REPEAT_TYPE_PROPERTY,
	typeId,
	propertyId,
	typeBlueprint,
});

export const repeatTypeCustomProperty = (typeId, propertyId) => ({
	type: REPEAT_TYPE_CUSTOM_PROPERTY,
	typeId,
	propertyId,
});

export const copyTypeCustomRepeatableItem = (typeId, repeatableItemId) => ({
	type: COPY_TYPE_CUSTOM_REPEATABLE_ITEM,
	typeId,
	repeatableItemId,
});

export const changeTypePropertyVersion = (
	typeId,
	propertyId,
	newPropertyType,
	typeBlueprint
) => ({
	type: CHANGE_TYPE_PROPERTY_VERSION,
	typeId,
	propertyId,
	newPropertyType,
	typeBlueprint,
});

export const addTypeConditionGroup = (typeId, typeBlueprint) => ({
	type: ADD_TYPE_CONDITION_GROUP,
	typeId,
	typeBlueprint,
});

export const addTypeCondition = (typeId, conditionId, typeBlueprint) => ({
	type: ADD_TYPE_CONDITION,
	typeId,
	conditionId,
	typeBlueprint,
});

export const updateTypeCondition = (
	typeId,
	conditionId,
	lhs,
	operator,
	rhs
) => ({
	type: UPDATE_TYPE_CONDITION,
	typeId,
	conditionId,
	lhs,
	operator,
	rhs,
});

export const deleteTypeCondition = (typeId, conditionId) => ({
	type: DELETE_TYPE_CONDITION,
	typeId,
	conditionId,
});

export const changeTypeStatus = (typeId, status) => ({
	type: CHANGE_TYPE_STATUS,
	typeId,
	status,
});

export const validate = (typeId) => ({
	type: VALIDATE_TYPE,
	typeId,
});

export const toggleTypeProperty = (typeId, propertyId, open) => ({
	type: TOGGLE_TYPE_PROPERTY,
	typeId,
	propertyId,
	open,
});

export const toggleTypeProperties = (typeId, open) => ({
	type: TOGGLE_TYPE_PROPERTIES,
	typeId,
	open,
});
