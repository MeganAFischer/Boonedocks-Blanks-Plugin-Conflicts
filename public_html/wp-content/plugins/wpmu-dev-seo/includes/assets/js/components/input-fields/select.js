import React from 'react';
import $ from 'jQuery';
import classnames from 'classnames';

export default class Select extends React.Component {
	static defaultProps = {
		small: false,
		tagging: false,
		placeholder: '',
		ajaxUrl: '',
		loadTextAjaxUrl: '',
		selectedValue: '',
		minimumResultsForSearch: 10,
		minimumInputLength: 3,
		multiple: false,
		options: {},
		templateResult: false,
		templateSelection: false,
		disabledOptions: [],
		disabled: false,
		onSelect: () => false,
		onLoading: () => false,
	};

	constructor(props) {
		super(props);

		this.props = props;
		this.state = {
			loadingText: false,
		};
		this.selectElement = React.createRef();
		this.selectElementContainer = React.createRef();
	}

	componentDidMount() {
		const $select = $(this.selectElement.current);
		$select.SUIselect2(this.getSelect2Args());

		this.includeSelectedValueAsDynamicOption();

		$select.on('change', (e) => this.handleChange(e));
	}

	includeSelectedValueAsDynamicOption() {
		if (this.props.selectedValue && this.noOptionsAvailable()) {
			if (this.props.tagging) {
				if (Array.isArray(this.props.selectedValue)) {
					this.props.selectedValue.forEach((selected) => {
						this.addOption(selected, selected, true);
					});
				} else {
					this.addOption(
						this.props.selectedValue,
						this.props.selectedValue,
						true
					);
				}
			} else if (this.props.loadTextAjaxUrl) {
				this.loadTextFromRemote();
			}
		}
	}

	loadTextFromRemote() {
		if (this.state.loadingText) {
			// Already in the middle of a remote call
			return;
		}

		this.setState({ loadingText: true });
		this.props.onLoading(true);

		$.get(this.props.loadTextAjaxUrl, {
			id: this.props.selectedValue,
		}).done((data) => {
			if (data && data.results && data.results.length) {
				data.results.forEach((result) => {
					this.addOption(result.id, result.text, true);
				});
			}

			this.setState({ loadingText: false });
			this.props.onLoading(false);
		});
	}

	addOption(value, text, selected) {
		const newOption = new Option(text, value, false, selected);
		$(this.selectElement.current).append(newOption).trigger('change');
	}

	noOptionsAvailable() {
		return !Object.keys(this.props.options).length;
	}

	componentWillUnmount() {
		const $select = $(this.selectElement.current);
		$select.off().SUIselect2('destroy');
	}

	getSelect2Args() {
		const $container = $(this.selectElementContainer.current);

		const args = {
			dropdownParent: $container,
			dropdownCssClass: 'sui-select-dropdown',
			minimumResultsForSearch: this.props.minimumResultsForSearch,
			multiple: this.props.multiple,
			tagging: this.props.tagging,
		};

		if (this.props.placeholder) {
			args.placeholder = this.props.placeholder;
			args.allowClear = true;
		}

		if (this.props.ajaxUrl) {
			args.ajax = { url: this.props.ajaxUrl };
			args.minimumInputLength = this.props.minimumInputLength;
		}

		if (this.props.templateResult) {
			args.templateResult = this.props.templateResult;
		}

		if (this.props.templateSelection) {
			args.templateSelection = this.props.templateSelection;
		}

		if (
			this.props.ajaxUrl &&
			this.props.tagging &&
			!this.props.processResults
		) {
			args.ajax.processResults = (response, request) => {
				if (response.results && !response.results.length) {
					return {
						results: [
							{
								id: request.term,
								text: request.term,
							},
						],
					};
				}

				return response;
			};
		}

		if (this.props.processResults) {
			args.ajax.processResults = this.props.processResults;
		}

		return args;
	}

	handleChange(e) {
		let value = e.target.value;
		if (this.props.multiple) {
			value = Array.from(
				e.target.selectedOptions,
				(option) => option.value
			);
		}

		this.props.onSelect(value);
	}

	printOptions(options) {
		const { selectedValue } = this.props,
			disabledOptions = this.props.disabledOptions.filter((opt) =>
				selectedValue.includes(opt)
			);

		return Object.keys(options).map((key) => {
			const value = options[key];

			if (typeof value === 'object' && value.label && value.options) {
				return (
					<optgroup key={key} label={value.label}>
						{this.printOptions(value.options)}
					</optgroup>
				);
			}
			return (
				<option
					key={key}
					value={key}
					disabled={disabledOptions.includes(key) ? true : undefined}
				>
					{value}
				</option>
			);
		});
	}

	render() {
		const optionsProp = this.props.options;
		let options;
		if (Object.keys(optionsProp).length) {
			options = this.printOptions(optionsProp);
		}

		return (
			<div
				ref={this.selectElementContainer}
				className="sui-select2-control"
			>
				<select
					className={classnames('sui-select', {
						'sui-select-sm': this.props.small,
					})}
					name={this.props.name && this.props.name}
					disabled={this.state.loadingText || this.props.disabled}
					value={
						this.props.selectedValue ||
						(this.props.multiple ? [] : undefined)
					}
					onChange={() => true}
					ref={this.selectElement}
					multiple={this.props.multiple}
				>
					{options}
				</select>
			</div>
		);
	}
}
