import { combineReducers } from 'redux';
import typesReducer from './types-reducer';

export default combineReducers({
	types: typesReducer,
});
