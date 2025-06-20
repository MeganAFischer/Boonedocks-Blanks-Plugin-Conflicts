import produce from 'immer';
import { parseInt } from 'lodash-es';
import { capitalizeWords, singular } from './string-utils';

export const CUSTOM_PROPERTY_STRUCTURE_NESTED = 'nested';
export const CUSTOM_PROPERTY_STRUCTURE_REPEATABLE = 'repeatable';
export const CUSTOM_PROPERTY_STRUCTURE_SIMPLE = 'simple';

function generatePropertyId(idParts) {
	return idParts.join('-');
}

function assignPropertyIds(properties, idParts = []) {
	return produce(properties, (draft) => {
		Object.keys(draft).forEach((propertyKey) => {
			const propertyIdParts = idParts.slice();
			propertyIdParts.push(propertyKey);

			const property = draft[propertyKey];
			if (property.id === false) {
				property.id = generatePropertyId(propertyIdParts);
			}

			if (isNestedProperty(property)) {
				property.properties = assignPropertyIds(
					property.properties,
					propertyIdParts
				);
			}
		});
	});
}

export function isOptionalProperty(property) {
	return !!property.optional;
}

export function isNestedProperty(property) {
	return (
		property.hasOwnProperty('properties') &&
		property.properties &&
		typeof property.properties === 'object'
	);
}

export function propertiesExist(properties) {
	return Object.keys(properties).length > 0;
}

/**
 * Makes new property instances from blueprints. Optional items are excluded.
 *
 * @param {Object} propertyBlueprints Blueprint property.
 *
 * @return {Object} New properties as object.
 */
export function makeNewProperties(propertyBlueprints) {
	const newProperties = _makeNewProperties(propertyBlueprints);
	return assignPropertyIds(newProperties);
}

function _makeNewProperties(propertyBlueprints) {
	const newProperties = {};
	Object.keys(propertyBlueprints).forEach((key) => {
		const propertyBlueprint = propertyBlueprints[key];
		if (!isOptionalProperty(propertyBlueprint)) {
			newProperties[key] = makeNewProperty(propertyBlueprint);
		}
	});

	return newProperties;
}

function makeNewProperty(propertyBlueprint) {
	const parts = [{}, propertyBlueprint];
	if (isNestedProperty(propertyBlueprint)) {
		parts.push({
			properties: _makeNewProperties(propertyBlueprint.properties),
		});
	}
	parts.push({ id: false });
	return Object.assign({}, ...parts);
}

/**
 * Makes copies of given properties.
 *
 * @param {Object} properties Property.
 *
 * @return {Object} Cloned properties.
 */
export function cloneProperties(properties) {
	const cloned = _cloneProperties(properties);
	return assignPropertyIds(cloned);
}

function _cloneProperties(properties) {
	const clonedProperties = {};
	Object.keys(properties).forEach((propertyKey) => {
		clonedProperties[propertyKey] = cloneProperty(properties[propertyKey]);
	});

	return clonedProperties;
}

function cloneProperty(property) {
	const parts = [{}, property];
	if (isNestedProperty(property)) {
		parts.push({
			properties: _cloneProperties(property.properties),
		});
	}
	parts.push({ id: false });
	return Object.assign({}, ...parts);
}

/**
 * Creates new instances of passed properties by overwriting everything with blueprint data except for the settings that the user can change.
 *
 * @param {Object} properties         Properties to be initialized.
 * @param {Object} propertyBlueprints Property values.
 *
 * @return {Object} Initialized properties.
 */
export function initializeProperties(properties, propertyBlueprints) {
	const initialized = _initializeProperties(properties, propertyBlueprints);
	return assignPropertyIds(initialized);
}

function _initializeProperties(properties, propertyBlueprints) {
	const initialized = {};
	Object.keys(properties).forEach((propertyKey) => {
		const property = properties[propertyKey];
		const propertyPath = findPropertyPath(properties, property.id);
		const propertyBlueprint = getPropertyBlueprintByPath(
			propertyBlueprints,
			propertyPath
		);

		initialized[propertyKey] = initializeProperty(
			property,
			propertyBlueprint
		);
	});
	return initialized;
}

function initializeProperty(property, propertyBlueprint) {
	const parts = [];
	parts.push(propertyBlueprint);
	const userSettings = [
		'type',
		'source',
		'value',
		'activeVersion',
		'disallowDeletion',
	];
	userSettings.forEach((valueToCopy) => {
		if (property.hasOwnProperty(valueToCopy)) {
			parts.push({
				[valueToCopy]: property[valueToCopy],
			});
		}
	});
	parts.push({
		// Close all accordions
		open: false,
	});
	if (isNestedProperty(property) && isNestedProperty(propertyBlueprint)) {
		parts.push({
			properties: _initializeProperties(
				property.properties,
				propertyBlueprint.properties
			),
		});
	}
	parts.push({ id: false });
	return Object.assign({}, ...parts);
}

export function addProperties(
	properties,
	propertyBlueprintIds,
	propertyBlueprints
) {
	propertyBlueprintIds.forEach((propertyBlueprintId) => {
		properties = addProperty(
			properties,
			propertyBlueprintId,
			propertyBlueprints
		);
	});

	return assignPropertyIds(properties);
}

export function addCustomProperty(
	properties,
	propertyName,
	label,
	type,
	structure,
	parentPropertyId = false
) {
	label = label ? label : propertyName;

	const newProperty = makeCustomProperty(
		propertyName,
		label,
		type,
		structure
	);
	const propertiesWithCustom = _addCustomProperty(
		properties,
		propertyName,
		newProperty,
		parentPropertyId
	);

	return assignPropertyIds(propertiesWithCustom);
}

export function repeatCustomProperty(properties, propertyId) {
	const updatedProperties = produce(properties, (draft) => {
		const property = getPropertyById(draft, propertyId);
		const firstItemKey = Object.keys(property.properties).shift();
		const type = property.properties[firstItemKey].type;
		const newKey = getNextRepeatableKey(property);
		const newProperty = makeCustomRepeatableSingle(type);
		newProperty.disallowDeletion = false;

		property.properties[newKey] = newProperty;
	});

	return assignPropertyIds(updatedProperties);
}

export function copyCustomRepeatableItem(properties, repeatableItemId) {
	const updatedProperties = produce(properties, (draft) => {
		const repeatableItem = getPropertyById(draft, repeatableItemId);
		const clonedRepeatableItem = cloneProperty(repeatableItem);
		clonedRepeatableItem.disallowDeletion = false;

		const parent = getPropertyParent(properties, repeatableItemId);
		const newKey = getNextRepeatableKey(parent);
		parent.properties[newKey] = clonedRepeatableItem;
	});

	return assignPropertyIds(updatedProperties);
}

function makeCustomRepeatableSingle(type) {
	return {
		id: false,
		type,
		isCustom: true,
		disallowDeletion: true,
		disallowFirstItemDeletionOnly: true,
		properties: {},
	};
}

export function isCustomProperty(property) {
	return property && property.isCustom;
}

function makeCustomProperty(propertyName, label, type, structure) {
	switch (structure) {
		case CUSTOM_PROPERTY_STRUCTURE_NESTED:
			return {
				id: false,
				label,
				type,
				isCustom: true,
				properties: {},
			};

		case CUSTOM_PROPERTY_STRUCTURE_REPEATABLE:
			return {
				id: false,
				label,
				isCustom: true,
				properties: {
					0: makeCustomRepeatableSingle(type),
				},
			};

		case CUSTOM_PROPERTY_STRUCTURE_SIMPLE:
		default:
			return {
				id: false,
				label,
				type: 'Dynamic',
				source: 'custom_text',
				value: '',
				isCustom: true,
			};
	}
}

function _addCustomProperty(
	properties,
	propertyName,
	property,
	parentPropertyId = false
) {
	return produce(properties, (draftProperties) => {
		if (parentPropertyId) {
			const parentProperty = getPropertyById(
				draftProperties,
				parentPropertyId
			);
			parentProperty.properties = _addCustomProperty(
				parentProperty.properties,
				propertyName,
				property
			);
		} else {
			draftProperties[propertyName] = property;
		}
	});
}

export function addProperty(
	properties,
	propertyBlueprintId,
	propertyBlueprints
) {
	return produce(properties, (draftProperties) => {
		Object.keys(propertyBlueprints).some((blueprintKey) => {
			const propertyBlueprint = propertyBlueprints[blueprintKey];
			const goDeeper =
				isNestedProperty(propertyBlueprint) &&
				draftProperties.hasOwnProperty(blueprintKey) &&
				isNestedProperty(draftProperties[blueprintKey]);

			if (propertyBlueprint.id === propertyBlueprintId) {
				draftProperties[blueprintKey] =
					makeNewProperty(propertyBlueprint);
				return true;
			} else if (goDeeper) {
				const property = draftProperties[blueprintKey];
				draftProperties[blueprintKey].properties = addProperty(
					property.properties,
					propertyBlueprintId,
					propertyBlueprint.properties
				);
			}

			return false;
		});

		return draftProperties;
	});
}

export function propertyHasAltVersions(property) {
	return (
		isNestedProperty(property) &&
		!!property.activeVersion &&
		property.properties.hasOwnProperty(property.activeVersion)
	);
}

export function getActivePropertyVersion(property) {
	return propertyHasAltVersions(property)
		? property.properties[property.activeVersion]
		: false;
}

export function getPropertyVersion(property, version) {
	const propertyHasVersion =
		isNestedProperty(property) &&
		property.properties.hasOwnProperty(version);

	return propertyHasVersion ? property.properties[version] : false;
}

export function isFlattenedProperty(property) {
	return isNestedProperty(property) && property.flatten;
}

export function deleteProperty(properties, id) {
	return produce(properties, (draftProperties) => {
		Object.keys(draftProperties).some((propertyKey) => {
			const property = draftProperties[propertyKey];
			if (id === property.id) {
				delete draftProperties[propertyKey];
				return true;
			} else if (isNestedProperty(property)) {
				const updatedNestedProperties = deleteProperty(
					property.properties,
					id
				);
				const deletedAltVersion =
					propertyHasAltVersions(property) &&
					Object.keys(updatedNestedProperties).length !==
						Object.keys(property.properties).length;
				const allSubPropertiesDeleted = !Object.keys(
					updatedNestedProperties
				).length;
				const deleteEmptyParent =
					!property.isCustom &&
					(deletedAltVersion || allSubPropertiesDeleted);

				if (deleteEmptyParent) {
					delete draftProperties[propertyKey];
				} else {
					draftProperties[propertyKey].properties =
						updatedNestedProperties;
				}
			}

			return false;
		});
	});
}

export function findPropertyPath(properties, id) {
	const path = [];

	Object.keys(properties).some((propertyKey) => {
		const property = properties[propertyKey];
		if (property.id === id) {
			path.unshift(propertyKey);
			return true;
		} else if (isNestedProperty(property)) {
			const nestedKeys = findPropertyPath(property.properties, id);
			if (nestedKeys.length) {
				path.unshift(propertyKey, 'properties', ...nestedKeys);
				return true;
			}
		}

		return false;
	});

	return path;
}

export function convertPropertyPathToBlueprintPath(propertyPath) {
	const convertedKeys = [];
	propertyPath.forEach((key) => {
		if (parseInt(key) > 0) {
			// A numeric key indicates a repeatable and the source properties only have 0 as repeatable key
			convertedKeys.push('0');
		} else {
			convertedKeys.push(key);
		}
	});
	return convertedKeys;
}

export function getPropertyById(properties, id) {
	return getPropertyByPath(properties, findPropertyPath(properties, id));
}

export function getPropertyName(properties, id) {
	const propertyPath = findPropertyPath(properties, id);
	return propertyPath.pop();
}

export function calculateLabelSingle(propertyName) {
	const labelWords = capitalizeWords(propertyName).split(' ');
	const lastWord = labelWords.pop();
	labelWords.push(singular(lastWord));

	return labelWords.join(' ');
}

export function getPropertyByPath(properties, path) {
	let property = properties;
	path.some((key) => {
		if (property.hasOwnProperty(key)) {
			property = property[key];
		} else {
			property = false;
			return true;
		}

		return false;
	});
	return property;
}

export function getBlueprintForProperty(
	properties,
	propertyId,
	propertyBlueprints
) {
	const propertyPath = findPropertyPath(properties, propertyId);
	return getPropertyBlueprintByPath(propertyBlueprints, propertyPath);
}

export function getPropertyBlueprintByPath(propertyBlueprints, path) {
	let property;
	// Try fetching a blueprint for the exact path first
	property = getPropertyByPath(propertyBlueprints, path);
	if (!property) {
		// That didn't work, probably because only the first item blueprint exists for repeatable properties
		// So let's try to adjust the path to use the first item blueprint wherever we can
		property = getPropertyByPath(
			propertyBlueprints,
			convertPropertyPathToBlueprintPath(path)
		);
	}
	return property;
}

export function changePropertyVersion(
	properties,
	propertyId,
	newPropertyVersion,
	propertyBlueprints
) {
	const propertyPath = findPropertyPath(properties, propertyId);
	const propertyBlueprint = getPropertyBlueprintByPath(
		propertyBlueprints,
		propertyPath
	);
	const alternateVersions = _makeNewProperties(propertyBlueprint.properties);
	const changedProperties = produce(properties, (draft) => {
		const updatedProperty = getPropertyByPath(draft, propertyPath);
		updatedProperty.activeVersion = newPropertyVersion;
		updatedProperty.properties = alternateVersions;
	});
	return assignPropertyIds(changedProperties);
}

export function isRepeatableProperty(property) {
	if (!isNestedProperty(property)) {
		return false;
	}

	const keys = Object.keys(property.properties);
	if (!keys.length) {
		// property.properties can never be empty in case of a repeatable
		// A custom nested property on the other hand can have empty properties
		return false;
	}

	const nonNumericKeys = keys.filter((key) => isNaN(key));
	return nonNumericKeys.length === 0;
}

function getNextRepeatableKey(property) {
	return Math.max(...Object.keys(property.properties)) + 1;
}

export function repeatProperty(properties, propertyId, propertyBlueprints) {
	const propertyPath = findPropertyPath(properties, propertyId);
	const property = getPropertyByPath(properties, propertyPath);
	const propertyBlueprint = getPropertyBlueprintByPath(
		propertyBlueprints,
		propertyPath
	);

	const repeatableKey = Object.keys(propertyBlueprint.properties).shift();
	const repeatable = propertyBlueprint.properties[repeatableKey];
	const newKey = getNextRepeatableKey(property);

	const newProperty = makeNewProperty(repeatable);
	if (
		repeatable.disallowDeletion &&
		repeatable.disallowFirstItemDeletionOnly
	) {
		newProperty.disallowDeletion = false;
	}

	if (repeatable.updateLabelNumber && repeatable.label) {
		newProperty.label = repeatable.label.replace('1', newKey + 1);
	}
	const updatedProperties = produce(properties, (draft) => {
		const updatedProperty = getPropertyByPath(draft, propertyPath);
		updatedProperty.properties[newKey] = newProperty;
	});
	return assignPropertyIds(updatedProperties);
}

export function getPropertyParent(properties, propertyId) {
	const propertyPath = findPropertyPath(properties, propertyId);
	propertyPath.pop(); // child key
	propertyPath.pop(); // 'properties'
	return getPropertyByPath(properties, propertyPath);
}

export function updateProperty(properties, id, source, value) {
	return produce(properties, (draft) => {
		const property = getPropertyById(draft, id);
		property.source = source;
		property.value = value;
	});
}

export function requiredPropertiesMissing(properties, propertyBlueprints) {
	let invalid = false;
	Object.keys(propertyBlueprints).some((propertyBlueprintKey) => {
		const propertyBlueprint = propertyBlueprints[propertyBlueprintKey];
		const propertyExists = properties.hasOwnProperty(propertyBlueprintKey);
		// We know that nested properties are not going to be required if the parent property itself is not required
		// An exception to this rule is local business -> review -> author but that doesn't matter because it is inside a repeatable and is always valid
		const requiredMissing = !propertyExists && propertyBlueprint.required;
		const nestedMissing =
			propertyExists && properties[propertyBlueprintKey].invalid;
		if (requiredMissing || nestedMissing) {
			invalid = true;
			return true;
		}

		return false;
	});

	return invalid;
}

export function findMissingPropertyKeys(needleProperties, haystackProperties) {
	const missing = [];
	Object.keys(haystackProperties).forEach((haystackKey) => {
		if (!needleProperties.hasOwnProperty(haystackKey)) {
			missing.push(haystackKey);
		}
	});

	return missing;
}

export function validateProperties(properties, propertyBlueprints) {
	return produce(properties, (draft) => {
		Object.keys(draft).forEach((propertyKey) => {
			const property = draft[propertyKey];
			const propertyBlueprint = getBlueprintForProperty(
				properties,
				property.id,
				propertyBlueprints
			);

			if (
				isNestedProperty(property) &&
				isNestedProperty(propertyBlueprint)
			) {
				property.properties = validateProperties(
					property.properties,
					propertyBlueprint.properties
				);
				property.invalid = requiredPropertiesMissing(
					property.properties,
					propertyBlueprint.properties
				);
			}
		});
	});
}

export function _toggleProperty(property, open) {
	property.open = open;

	if (!open && isNestedProperty(property)) {
		_toggleProperties(property.properties, false);
	}
}

export function _toggleProperties(properties, open) {
	Object.keys(properties).forEach((propertyKey) => {
		_toggleProperty(properties[propertyKey], open);
	});
}

export function toggleProperty(properties, id, open) {
	return produce(properties, (draft) => {
		_toggleProperty(getPropertyById(draft, id), open);
	});
}

export function toggleProperties(properties, open) {
	return produce(properties, (draft) => {
		_toggleProperties(draft, open);
	});
}
