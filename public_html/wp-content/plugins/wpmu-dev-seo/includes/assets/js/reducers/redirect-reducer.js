import { uniqueId } from 'lodash-es';
import { validateStore } from '../utils/redirect-utils';

export default (state = {}, action) => {
	switch (action.type) {
		case 'UPDATE_STORE':
			const payload = Object.assign(
				{
					id: '',
					source: '',
					destination: '',
					dstDisabled: false,
					rules: [],
					rulesEnabled: false,
					ruleKeys: [],
					title: '',
					options: [],
					type: state.defaultType,
					bulkUpdating: false,
					bulkTo: false,
					bulkType: false,
					valid: false,
					loading: false,
				},
				action.payload
			);

			state = {
				...state,
				...payload,
				ruleKeys: payload.rules.map(() => uniqueId()),
				dstDisabled: !payload.destination && payload.rules.length,
				rulesEnabled: payload.rules.length > 0,
			};

			break;

		case 'UPDATE_FROM':
			state = { ...state, source: action.payload.source };

			if (!action.payload.valid) {
				return { ...state, valid: false };
			}

			break;

		case 'UPDATE_TO':
			state = { ...state, destination: action.payload };

			break;

		case 'UPDATE_TYPE':
			state = { ...state, ...action.payload };

			break;

		case 'UPDATE_DEFAULT_TYPE':
			state = {
				...state,
				...action.payload,
				type: action.payload.defaultType,
			};

			break;

		case 'UPDATE_TITLE':
			state = { ...state, title: action.payload.title };

			if (!action.payload.valid) {
				return { ...state, valid: false };
			}

			break;

		case 'TOGGLE_TO':
			state = {
				...state,
				dstDisabled: !state.dstDisabled,
			};

			break;

		case 'TOGGLE_BULK_TO':
			state = {
				...state,
				bulkTo: !state.bulkTo,
			};

			break;

		case 'TOGGLE_BULK_TYPE':
			state = {
				...state,
				bulkType: !state.bulkType,
			};

			break;

		case 'TOGGLE_RULES':
			state = {
				...state,
				rulesEnabled: !state.rulesEnabled,
			};

			break;

		case 'CREATE_RULE':
			state = {
				...state,
				rules: [
					...state.rules,
					{
						indicate: '0',
						countries: [],
						url: '',
					},
				],
				ruleKeys: [...state.ruleKeys, uniqueId()],
			};

			break;

		case 'UPDATE_RULE':
			const { rule, index } = action.payload;

			// This is required to trigger rule update.
			state = {
				...state,
				rules: [
					...state.rules.slice(0, index),
					rule,
					...state.rules.slice(index + 1),
				],
			};

			break;

		case 'DELETE_RULE':
			state = {
				...state,
				rules: [
					...state.rules.slice(0, action.payload),
					...state.rules.slice(action.payload + 1),
				],
				ruleKeys: [
					...state.ruleKeys.slice(0, action.payload),
					...state.ruleKeys.slice(action.payload + 1),
				],
				deletingRule: false,
			};

			break;

		case 'UPDATE_OPTIONS':
			const { options } = state;
			const { option, value } = action.payload;

			if (value) {
				if (!options.includes(option)) {
					state = { ...state, options: [...options, option] };
				}
			} else {
				const optindex = options.indexOf(option);

				if (optindex !== -1) {
					state = {
						...state,
						options: [
							...options.slice(0, optindex),
							...options.slice(optindex + 1),
						],
					};
				}
			}

			break;

		case 'DELETING_RULE':
			return { ...state, deletingRule: action.payload };

		default:
			state = { ...state, ...action.payload };
	}

	return { ...state, valid: validateStore(state) };
};
