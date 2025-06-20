export default (state = {}, action) => {
	switch (action.type) {
		case 'UPDATE_SELECTED':
			return {
				...state,
				selected: action.selected,
			};

		case 'UPDATE_OPTION':
			return {
				...state,
				[state.selected]: {
					...state[state.selected],
					options: {
						...state[state.selected].options,
						[action.key]: action.value,
					},
				},
			};

		case 'UPDATE_PROP':
			return {
				...state,
				[state.selected]: {
					...state[state.selected],
					[action.key]: action.value,
				},
			};

		case 'UPDATE_SUBMODULE':
			const submodules = {};

			action.value.forEach((val) => {
				submodules[val.name] = { ...state[val.name], ...val.value };
			});

			return {
				...state,
				...submodules,
			};

		case 'TOGGLE_LOADING':
			return {
				...state,
				[state.selected]: {
					...state[state.selected],
					loading: !state[state.selected].loading,
				},
			};

		default:
			return state;
	}
};
