export default class PropUtil {
	static getValidProps(orgProps, propNames) {
		return Object.keys(orgProps)
			.filter((propName) => propNames.includes(propName))
			.reduce((obj, propName) => {
				obj[propName] = orgProps[propName];
				return obj;
			}, {});
	}
}
