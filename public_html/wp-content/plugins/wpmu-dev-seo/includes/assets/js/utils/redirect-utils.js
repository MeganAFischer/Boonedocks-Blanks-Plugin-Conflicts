import ConfigValues from '../es6/config-values';
import fieldWithValidation from '../components/field-with-validation';
import TextInputField from '../components/form-fields/text-input-field';
import {
	isNonEmpty,
	isRegexStringValid,
	isRelativeUrlValid,
	isUrlValid,
	isValuePlainText,
	Validator,
} from './validators';
import { __ } from '@wordpress/i18n';

const homeUrl = ConfigValues.get('home_url', 'admin');

export const isNonRedirectType = (type) => {
	return ConfigValues.get('non_redirect_types', 'redirects').includes(
		parseInt(type)
	);
};

export const getRedirectTypes = () => {
	return {
		301: __('Permanent (301)', 'wds'),
		302: __('Temporary (302)', 'wds'),
		307: __('Temporary Redirect (307)', 'wds'),
		410: __('Content Deleted (410)', 'wds'),
		451: __(
			'Content Unavailable for Legal Reasons (451)',
			'wds'
		),
	};
};

export const validateSource = (data) => {
	const { source } = data;

	if (!source) {
		return false;
	}

	const { options } = data;

	const isRegex = options.includes('regex');

	if (isRegex) {
		if (!isRegexStringValid(source) || !source.startsWith(homeUrl)) {
			return false;
		}
	} else if (
		!isUrlValid(source) ||
		(!isRelativeUrlValid(source) && !source.startsWith(homeUrl))
	) {
		return false;
	}

	return true;
};

export const validateStore = (data) => {
	if (!Object.keys(data).length) {
		return true;
	}

	if (data.loading) {
		return false;
	}

	const { rulesEnabled, rules } = data;

	const rulesInvalid = rules.find(
		(rule) => !rule.indicate || !rule.countries.length || !rule.url
	);

	const { bulkUpdating, bulkTo, destination, bulkType, type } = data;

	if (bulkUpdating) {
		if (
			(!bulkType || !isNonRedirectType(type)) &&
			rulesEnabled &&
			(!rules.length || rulesInvalid)
		) {
			return false;
		}

		if (!bulkType && !rulesEnabled && (!bulkTo || !destination)) {
			return false;
		}

		return true;
	}

	const { source } = data;

	if (!validateSource(data)) {
		return false;
	}

	if (isNonRedirectType(type)) {
		return true;
	}

	if (rulesEnabled && (!rules.length || rulesInvalid)) {
		return false;
	}

	if (
		destination?.url &&
		getFullUrl(source) === getFullUrl(destination.url)
	) {
		return false;
	}

	const { dstDisabled } = data;

	if (
		(rulesEnabled && !dstDisabled && (!destination || rulesInvalid)) ||
		(!rulesEnabled && !destination)
	) {
		return false;
	}

	if (!validateCountries(data)) {
		return false;
	}

	return true;
};

export const validateCountries = (data) => {
	if (data.rules.length < 2) {
		return true;
	}

	for (let i = 0; i < data.rules.length - 1; i++) {
		for (let j = i + 1; j < data.rules.length; j++) {
			const commonCountries = data.rules[i].countries.filter((country) =>
				data.rules[j].countries.includes(country)
			);

			if (commonCountries.length > 0) {
				return false;
			}
		}
	}

	return true;
};

export const getRequestData = (data) => {
	let validProps = [];

	const { bulkUpdating, bulkTo, bulkType, type, rulesEnabled, dstDisabled } =
		data;

	if (bulkUpdating) {
		if (bulkType) {
			validProps.push('type');
		}

		if ((bulkType && !isNonRedirectType(type)) || !bulkType) {
			if (rulesEnabled) {
				if (!dstDisabled && bulkTo) {
					validProps.push('destination');
				}

				validProps.push('rules');
			} else if (bulkTo) {
				validProps.push('destination');
			}
		}
	} else {
		validProps = ['id', 'source', 'type', 'options', 'title'];

		if (!isNonRedirectType(type)) {
			if (rulesEnabled) {
				if (!dstDisabled) {
					validProps.push('destination');
				}

				validProps.push('rules');
			} else {
				validProps.push('destination');
			}
		}
	}

	const result = Object.keys(data)
		.filter((propName) => validProps.includes(propName))
		.reduce((obj, propName) => {
			obj[propName] = data[propName];
			return obj;
		}, {});

	return result;
};

export const populateDestination = (data) => {
	const result = { ...data };

	if (data.destination) {
		if (data.destination.type?.toLowerCase() === 'url') {
			result.destination = data.destination.url;
		} else {
			result.destination = {
				id: data.destination.id,
				type: data.destination._type,
			};
		}
	}

	if (data.rules) {
		result.rules = data.rules.map((rule) => {
			const ruleUrl = rule.url;

			if (ruleUrl.hasOwnProperty('type')) {
				rule.url =
					ruleUrl.type.toLowerCase() === 'url'
						? ruleUrl.url
						: {
								id: ruleUrl.id,
								type: ruleUrl._type,
						  };
			}

			return { ...rule };
		});
	}

	return result;
};

export const getFullUrl = (url) => {
	if (url[0] === '/') {
		url = homeUrl + url;
	}

	return url.replace(/\/$/, '');
};

export const SourceFieldNonRegex = fieldWithValidation(TextInputField, [
	isNonEmpty,
	new Validator(
		isUrlValid,
		__(
			'You need to use an absolute URL like https://domain.com/new-url or start with a slash /new-url.',
			'wds'
		)
	),
	new Validator((url) => {
		const isRelative = isRelativeUrlValid(url);
		const startsWithHome = (url.replace(/\/$/, '') + '/').startsWith(
			homeUrl
		);

		return isRelative || startsWithHome;
	}, __('You need to enter a URL belonging to the current site.', 'wds')),
	new Validator((url) => {
		return (
			url.endsWith('/') ||
			url.replace(/\/$/, '') !== homeUrl.replace(/\/$/, '')
		);
	}, __('If you want to use Home URL, please use a relative URL (/) instead.', 'wds')),
]);

export const SourceFieldRegex = fieldWithValidation(TextInputField, [
	isNonEmpty,
	new Validator(
		isUrlValid,
		__(
			'You need to use an absolute URL like https://domain.com/new-url or start with a slash /new-url.',
			'wds'
		)
	),
	new Validator(isRegexStringValid, __('This regex is invalid.', 'wds')),
	new Validator((value) => value.indexOf(homeUrl) !== -1),
]);

export const TitleField = fieldWithValidation(TextInputField, [
	isValuePlainText,
]);
