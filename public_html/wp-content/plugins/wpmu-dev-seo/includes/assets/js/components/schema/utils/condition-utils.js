import produce from 'immer';

function findConditionGroupIndex(conditionGroups, id) {
	return conditionGroups.findIndex(
		(conditions) => findConditionIndex(conditions, id) > -1
	);
}

function findConditionIndex(conditions, id) {
	return conditions.findIndex((condition) => condition.id === id);
}

export function getDefaultCondition(conditionGroups, typeBlueprint) {
	const newId = generateNextConditionId(conditionGroups);
	const fallback = {
		id: newId,
		lhs: 'post_type',
		operator: '=',
		rhs: 'post',
	};
	return typeBlueprint.condition
		? Object.assign({}, typeBlueprint.condition, { id: newId })
		: fallback;
}

function generateNextConditionId(conditionGroups) {
	let id = 0;
	conditionGroups.forEach((conditions) => {
		conditions.forEach((condition) => {
			if (condition.id > id) {
				id = condition.id;
			}
		});
	});
	return ++id;
}

export function addConditionGroup(conditionGroups, typeBlueprint) {
	const condition = getDefaultCondition(conditionGroups, typeBlueprint);
	return produce(conditionGroups, (draft) => {
		draft.push([condition]);
	});
}

export function addCondition(conditionGroups, id, typeBlueprint) {
	const groupIndex = findConditionGroupIndex(conditionGroups, id);
	const conditionIndex = findConditionIndex(conditionGroups[groupIndex], id);
	const newConditionIndex = conditionIndex + 1;
	const condition = getDefaultCondition(conditionGroups, typeBlueprint);

	return produce(conditionGroups, (draft) => {
		draft[groupIndex].splice(newConditionIndex, 0, condition);
	});
}

export function updateCondition(conditionGroups, id, lhs, operator, rhs) {
	const groupIndex = findConditionGroupIndex(conditionGroups, id);
	const conditionIndex = findConditionIndex(conditionGroups[groupIndex], id);
	return produce(conditionGroups, (draft) => {
		draft[groupIndex][conditionIndex] = {
			id,
			lhs,
			operator,
			rhs,
		};
	});
}

export function deleteCondition(conditionGroups, id) {
	const groupIndex = findConditionGroupIndex(conditionGroups, id);
	const group = conditionGroups[groupIndex];
	if (group.length === 1) {
		return produce(conditionGroups, (draft) => {
			draft.splice(groupIndex, 1);
		});
	}
	const conditionIndex = findConditionIndex(group, id);
	return produce(conditionGroups, (draft) => {
		draft[groupIndex].splice(conditionIndex, 1);
	});
}

export function cloneConditions(conditionGroups) {
	let id = 0;
	const clonedConditionGroup = [];
	conditionGroups.forEach((conditions) => {
		const clonedConditions = [];
		conditions.forEach((condition) => {
			clonedConditions.push(Object.assign({}, condition, { id: ++id }));
		});
		clonedConditionGroup.push(clonedConditions);
	});
	return clonedConditionGroup;
}
