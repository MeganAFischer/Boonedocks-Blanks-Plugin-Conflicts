import React from 'react';

const fieldWithValidation = function (WrappedComponent, validator) {
	return class extends React.Component {
		static defaultProps = {
			value: '',
			validateOnInit: false,
			onChange: () => false,
		};

		constructor(props) {
			super(props);

			const { value, validateOnInit } = this.props;

			if (validateOnInit) {
				this.handleChange(value);
			} else {
				this.isValid = true;
				this.errorMessage = '';
			}
		}

		validateValue(value) {
			if (Array.isArray(validator)) {
				const invalid = validator.find((_validator) => {
					return !this.runValidator(_validator, value);
				});

				this.isValid = !invalid;
				this.errorMessage = invalid
					? this.getErrorMessage(invalid)
					: '';
			} else {
				this.isValid = this.runValidator(validator, value);
				this.errorMessage = this.isValid
					? ''
					: this.getErrorMessage(validator);
			}
		}

		getErrorMessage(_validator) {
			let errorMessage = '';
			if (_validator.getError instanceof Function) {
				errorMessage = _validator.getError();
			}
			return errorMessage;
		}

		runValidator(_validator, value) {
			let isValid;
			if (_validator.isValid instanceof Function) {
				isValid = _validator.isValid(value);
			} else if (_validator instanceof Function) {
				isValid = _validator(value);
			}

			return isValid;
		}

		handleChange(value) {
			this.validateValue(value);
			this.props.onChange(value, this.isValid);
		}

		render() {
			return (
				<WrappedComponent
					{...this.props}
					isValid={this.isValid}
					errorMessage={this.errorMessage}
					onChange={(value) => this.handleChange(value)}
				/>
			);
		}
	};
};

export default fieldWithValidation;
