import {
	addCustomProperty,
	addProperties,
	changePropertyVersion,
	cloneProperties,
	copyCustomRepeatableItem,
	deleteProperty,
	initializeProperties,
	makeNewProperties,
	repeatCustomProperty,
	repeatProperty,
	requiredPropertiesMissing,
	toggleProperties,
	toggleProperty,
	updateProperty,
	validateProperties,
} from './property-utils';
import {
	addCondition,
	addConditionGroup,
	cloneConditions,
	deleteCondition,
	updateCondition,
} from './condition-utils';
import produce from 'immer';
import SchemaTypeTransformer from './schema-type-transformer';
import SchemaTypeBlueprints, {
	CUSTOM_TYPE,
} from '../resources/schema-type-blueprints';
import { parseInt } from 'lodash-es';

function generateTypeId(typeString, index) {
	return typeString + '-' + index;
}

function generateNextTypeId(schemaTypes, typeString) {
	let newIndex = 0;
	Object.keys(schemaTypes).forEach((typeId) => {
		const index = parseInt(typeId.split('-')[1]);
		if (newIndex < index) {
			newIndex = index;
		}
	});
	return generateTypeId(typeString, ++newIndex);
}

export function initializeTypes(schemaTypes) {
	let id = 0;
	const initialized = {};
	Object.keys(schemaTypes).forEach((schemaTypeKey) => {
		const schemaType = schemaTypes[schemaTypeKey];
		const typeId = generateTypeId(schemaType.type, ++id);
		const typeBlueprint = SchemaTypeBlueprints.getTypeBlueprint(
			schemaType.type,
			schemaType.properties
		);
		initialized[typeId] = initializeType(schemaType, typeBlueprint);
	});
	return initialized;
}

export function initializeType(type, typeBlueprint) {
	const transformer = new SchemaTypeTransformer();
	const properties = initializeProperties(
		transformer.transformProperties(type.type, type.properties),
		typeBlueprint.properties
	);
	const conditions = cloneConditions(type.conditions);
	return Object.assign({}, type, {
		conditions,
		properties,
	});
}

export function addType(
	schemaTypes,
	label,
	conditions,
	version,
	typeBlueprint
) {
	const typeString = typeBlueprint.type;
	const newTypeId = generateNextTypeId(schemaTypes, typeString);

	return produce(schemaTypes, (draft) => {
		draft[newTypeId] = {
			label,
			type: typeString,
			version,
			invalid: false,
			conditions,
			properties: makeNewProperties(typeBlueprint.properties),
		};
	});
}

export function duplicateType(schemaTypes, typeId) {
	const schemaType = schemaTypes[typeId];
	const typeString = schemaType.type;
	const newTypeId = generateNextTypeId(schemaTypes, typeString);

	return produce(schemaTypes, (draft) => {
		draft[newTypeId] = {
			label: schemaType.label,
			type: typeString,
			version: schemaType.version || false,
			invalid: schemaType.invalid,
			conditions: cloneConditions(schemaType.conditions),
			properties: cloneProperties(schemaType.properties),
		};
	});
}

export function deleteType(schemaTypes, typeId) {
	return produce(schemaTypes, (draft) => {
		delete draft[typeId];
	});
}

export function renameType(schemaTypes, typeId, newLabel) {
	return produce(schemaTypes, (draft) => {
		const schemaType = draft[typeId];
		schemaType.label = newLabel;
	});
}

export function resetTypeProperties(schemaTypes, typeId, typeBlueprint) {
	return produce(schemaTypes, (draft) => {
		const schemaType = draft[typeId];
		schemaType.properties = makeNewProperties(typeBlueprint.properties);
	});
}

export function addTypeProperties(
	schemaTypes,
	typeId,
	propertyBlueprintIds,
	typeBlueprint
) {
	return produce(schemaTypes, (draft) => {
		const schemaType = draft[typeId];
		schemaType.properties = addProperties(
			schemaType.properties,
			propertyBlueprintIds,
			typeBlueprint.properties
		);
	});
}

export function addTypeCustomProperty(
	schemaTypes,
	typeId,
	propertyName,
	label,
	propertyType,
	structure,
	parentPropertyId = false
) {
	return produce(schemaTypes, (draft) => {
		const schemaType = draft[typeId];
		schemaType.properties = addCustomProperty(
			schemaType.properties,
			propertyName,
			label,
			propertyType,
			structure,
			parentPropertyId
		);
	});
}

export function copyTypeCustomRepeatableItem(
	schemaTypes,
	typeId,
	repeatableItemId
) {
	return produce(schemaTypes, (draft) => {
		const schemaType = draft[typeId];
		schemaType.properties = copyCustomRepeatableItem(
			schemaType.properties,
			repeatableItemId
		);
	});
}

export function updateTypeProperty(
	schemaTypes,
	typeId,
	propertyId,
	source,
	value
) {
	return produce(schemaTypes, (draft) => {
		const schemaType = draft[typeId];
		schemaType.properties = updateProperty(
			schemaType.properties,
			propertyId,
			source,
			value
		);
	});
}

export function deleteTypeProperty(schemaTypes, typeId, propertyId) {
	return produce(schemaTypes, (draft) => {
		const schemaType = draft[typeId];
		schemaType.properties = deleteProperty(
			schemaType.properties,
			propertyId
		);
	});
}

export function repeatTypeProperty(
	schemaTypes,
	typeId,
	propertyId,
	typeBlueprint
) {
	return produce(schemaTypes, (draft) => {
		const schemaType = draft[typeId];
		schemaType.properties = repeatProperty(
			schemaType.properties,
			propertyId,
			typeBlueprint.properties
		);
	});
}

export function repeatTypeCustomProperty(schemaTypes, typeId, propertyId) {
	return produce(schemaTypes, (draft) => {
		const schemaType = draft[typeId];
		schemaType.properties = repeatCustomProperty(
			schemaType.properties,
			propertyId
		);
	});
}

export function changeTypePropertyVersion(
	schemaTypes,
	typeId,
	propertyId,
	newPropertyType,
	typeBlueprint
) {
	return produce(schemaTypes, (draft) => {
		const schemaType = draft[typeId];
		schemaType.properties = changePropertyVersion(
			schemaType.properties,
			propertyId,
			newPropertyType,
			typeBlueprint.properties
		);
	});
}

export function addTypeConditionGroup(schemaTypes, typeId, typeBlueprint) {
	return produce(schemaTypes, (draft) => {
		const schemaType = draft[typeId];

		schemaType.conditions = addConditionGroup(
			schemaType.conditions,
			typeBlueprint
		);
	});
}

export function addTypeCondition(
	schemaTypes,
	typeId,
	conditionId,
	typeBlueprint
) {
	return produce(schemaTypes, (draft) => {
		const schemaType = draft[typeId];

		schemaType.conditions = addCondition(
			schemaType.conditions,
			conditionId,
			typeBlueprint
		);
	});
}

export function updateTypeCondition(
	schemaTypes,
	typeId,
	conditionId,
	lhs,
	operator,
	rhs
) {
	return produce(schemaTypes, (draft) => {
		const schemaType = draft[typeId];
		schemaType.conditions = updateCondition(
			schemaType.conditions,
			conditionId,
			lhs,
			operator,
			rhs
		);
	});
}

export function deleteTypeCondition(schemaTypes, typeId, conditionId) {
	return produce(schemaTypes, (draft) => {
		const schemaType = draft[typeId];
		schemaType.conditions = deleteCondition(
			schemaType.conditions,
			conditionId
		);
	});
}

export function changeTypeStatus(schemaTypes, typeId, status) {
	return produce(schemaTypes, (draft) => {
		const schemaType = draft[typeId];
		schemaType.disabled = !status;
	});
}

export function typesValid(schemaTypes) {
	let valid = true;
	Object.keys(schemaTypes).forEach((typeId) => {
		if (schemaTypes[typeId].invalid) {
			valid = false;
		}
	});
	return valid;
}

export function validate(schemaTypes, typeId) {
	if (isCustomType(schemaTypes[typeId].type)) {
		return schemaTypes;
	}

	return produce(schemaTypes, (draft) => {
		draft[typeId] = validateSingleType(schemaTypes[typeId]);
	});
}

function validateSingleType(schemaType) {
	return produce(schemaType, (schemaTypeDraft) => {
		const schemaTypeBlueprint = SchemaTypeBlueprints.getTypeBlueprint(
			schemaTypeDraft.type
		);
		schemaTypeDraft.properties = validateProperties(
			schemaTypeDraft.properties,
			schemaTypeBlueprint.properties
		);
		schemaTypeDraft.invalid = requiredPropertiesMissing(
			schemaTypeDraft.properties,
			schemaTypeBlueprint.properties
		);
	});
}

export function validateTypes(schemaTypes) {
	return produce(schemaTypes, (draft) => {
		Object.keys(schemaTypes).forEach((typeId) => {
			const isTypeCustom = isCustomType(schemaTypes[typeId].type);
			if (!isTypeCustom) {
				draft[typeId] = validateSingleType(schemaTypes[typeId]);
			}
		});
	});
}

export function toggleTypeProperty(schemaTypes, typeId, propertyId, open) {
	return produce(schemaTypes, (draft) => {
		draft[typeId].properties = toggleProperty(
			draft[typeId].properties,
			propertyId,
			open
		);
	});
}

export function toggleTypeProperties(schemaTypes, typeId, open) {
	return produce(schemaTypes, (draft) => {
		draft[typeId].properties = toggleProperties(
			draft[typeId].properties,
			open
		);
	});
}

export function isCustomType(type) {
	return type === CUSTOM_TYPE;
}
