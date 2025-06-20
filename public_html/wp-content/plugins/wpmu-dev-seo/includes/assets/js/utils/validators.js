export const isNonEmpty = (value) => {
	return value && value.trim();
};

export const isValuePlainText = (value) => {
	const doc = new DOMParser().parseFromString(value, 'text/html');
	return doc?.body?.textContent === value;
};

export const hasWhitelistCharactersOnly = (value) => {
	return !!value.match(/^[@.'_\-\sa-zA-Z0-9]+$/);
};

export const isUrlValid = (string) => {
	return isRelativeUrlValid(string) || isAbsoluteUrlValid(string);
};

export const isAlphabetic = (string) => {
	return !!string.match(/^[a-zA-Z]*$/);
};

export const isEmailValid = (string) => {
	return !!string
		.toLowerCase()
		.match(
			/^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
		);
};

export const isRelativeUrlValid = (string) => {
	return (
		string.startsWith('/') &&
		isAbsoluteUrlValid('https://dummydomain.com' + string)
	);
};

export const isRegexStringValid = (string) => {
	try {
		new RegExp(string);
	} catch (error) {
		return false;
	}
	return true;
};

export const isAbsoluteUrlValid = (string) => {
	let url;
	try {
		url = new URL(string);
	} catch (_) {
		return false;
	}

	return url.protocol === 'http:' || url.protocol === 'https:';
};

export class Validator {
	constructor(func, errorMessage) {
		this.func = func;
		this.errorMessage = errorMessage;
	}

	isValid(value) {
		return this.func(value);
	}

	getError() {
		return this.errorMessage;
	}
}

export class hasNotWhitespace {}
