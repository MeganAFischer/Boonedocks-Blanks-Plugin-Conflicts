import React from 'react';
import $ from 'jQuery';
import ConfigValues from '../../es6/config-values';
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { isUrlValid } from '../../utils/validators';

const restURL = ConfigValues.get('rest_url', 'admin');
const postTypes = ConfigValues.get('post_types', 'admin');

export default class UrlInput extends React.Component {
	static defaultProps = {
		onSelect: () => false,
	};

	constructor(props) {
		super(props);

		this.state = {
			value: '',
			results: [],
			temp: false,
			selected: this.props.value,
			loading: false,
			showResults: false,
		};

		this.componentRef = React.createRef();
	}

	componentDidMount() {
		document.addEventListener('click', this.handleClickOutside);
		document.addEventListener('keydown', this.handleKeydownOutside);
	}

	componentWillUnmount() {
		document.removeEventListener('click', this.handleClickOutside);
	}

	handleClickOutside = (event) => {
		if (!this.componentRef.current) {
			return;
		}

		if (!this.componentRef.current.contains(event.target)) {
			this.setState({ showResults: false });
		}
	};

	handleKeydownOutside = (event) => {
		if (event.key !== 'Escape') {
			return;
		}

		this.xhr.abort();

		this.setState({ loading: false });
		this.setSelected(this.state.temp);
	};

	handleBtnClick() {
		this.xhr.abort();

		this.setState({ loading: false });
		this.setSelected({
			type: 'URL',
			url: encodeURI(this.state.value),
		});
	}

	handleKeyDown(e) {
		if (e.key !== 'Enter' && e.key !== 'Escape') {
			return;
		}

		e.stopPropagation();

		this.xhr.abort();

		this.setState({ loading: false });

		if (e.key === 'Escape') {
			this.setSelected(this.state.temp);
		} else {
			this.setSelected({
				type: 'URL',
				url: encodeURI(this.state.value),
			});
		}
	}

	handleChange(value) {
		this.setState({ value });

		if (value.length < 2) {
			return;
		}

		this.setState({ loading: true, results: [] });

		if (this.xhr) {
			this.xhr.abort();
		}

		this.xhr = $.ajax({
			url: restURL + 'wp/v2/search',
			type: 'GET',
			data: {
				search: value,
				per_page: 20,
				type: 'post',
				_locale: 'user',
			},
			beforeSend: (xhr) => {
				xhr.setRequestHeader('Accept', 'application/json,*.*;q=0.1');
			},
			success: (data) => {
				const results = data.map((d) => {
					return {
						id: d.id,
						type: postTypes[d.subtype],
						_type: d.subtype,
						title: d.title,
						url: d.url,
					};
				});

				if (!results.length && isUrlValid(value)) {
					results.push({
						url: value,
						type: __('URL', 'wds'),
					});
				}

				this.setState({ results, showResults: true, loading: false });
			},
		});
	}

	handleClickInput() {
		this.setState({ showResults: true });
	}

	setSelected(selected) {
		this.setState({ selected, temp: false, value: '' });
		this.props.onSelect(selected);
	}

	editSelected() {
		const { selected } = this.state;

		this.setState({ temp: selected, selected: false });

		this.handleChange(selected.url);
	}

	unsetSelected() {
		this.setState({ selected: false });
		this.props.onSelect('');
	}

	render() {
		return (
			<div className="wds-url-input-wrapper" ref={this.componentRef}>
				{this.renderInner()}
			</div>
		);
	}

	renderInner() {
		const { value, selected } = this.state;

		if (selected) {
			return (
				<>
					<div className="wds-url-input-selected sui-form-control">
						<span>
							<a href={selected.url}>
								{selected.title ? selected.title : selected.url}
							</a>
						</span>

						<button
							className="sui-button-icon"
							onClick={() => this.editSelected()}
						>
							<span
								className="sui-icon-pencil"
								aria-hidden="true"
							></span>
						</button>

						<button
							className="sui-button-icon"
							onClick={() => this.unsetSelected()}
						>
							<span
								className="sui-icon-close"
								aria-hidden="true"
							></span>
						</button>
					</div>
				</>
			);
		}

		const validProps = [
			'id',
			'name',
			'type',
			'placeholder',
			'disabled',
			'readOnly',
			'className',
		];
		const inputProps = Object.keys(this.props)
			.filter(
				(propName) =>
					validProps.includes(propName) && this.props[propName]
			)
			.reduce((obj, propName) => {
				obj[propName] = this.props[propName];
				return obj;
			}, {});

		inputProps.value = value;

		return (
			<>
				<input
					{...inputProps}
					className={classnames(
						'wds-url-input',
						'sui-form-control',
						this.props.className
					)}
					onKeyDown={(e) => this.handleKeyDown(e)}
					onChange={(e) => this.handleChange(e.target.value)}
					onClick={() => this.handleClickInput()}
				/>
				<button
					className="wds-url-input-btn sui-button-icon"
					disabled={!value}
					onClick={() => this.handleBtnClick()}
				>
					<svg
						xmlns="http://www.w3.org/2000/svg"
						viewBox="-2 -2 24 24"
						width="24"
						height="24"
						aria-hidden="true"
						focusable="false"
					>
						<path d="M6.734 16.106l2.176-2.38-1.093-1.028-3.846 4.158 3.846 4.157 1.093-1.027-2.176-2.38h2.811c1.125 0 2.25.03 3.374 0 1.428-.001 3.362-.25 4.963-1.277 1.66-1.065 2.868-2.906 2.868-5.859 0-2.479-1.327-4.896-3.65-5.93-1.82-.813-3.044-.8-4.806-.788l-.567.002v1.5c.184 0 .368 0 .553-.002 1.82-.007 2.704-.014 4.21.657 1.854.827 2.76 2.657 2.76 4.561 0 2.472-.973 3.824-2.178 4.596-1.258.807-2.864 1.04-4.163 1.04h-.02c-1.115.03-2.229 0-3.344 0H6.734z"></path>
					</svg>
				</button>
				{this.renderSearchResults()}
			</>
		);
	}

	renderSearchResults() {
		const { loading } = this.state;

		if (loading) {
			return (
				<div className="wds-url-search-results">
					<p className="wds-url-search-loading">
						<span
							className="sui-icon-loader sui-loading"
							aria-hidden="true"
						/>{' '}
						{__('Searching…', 'wds')}
					</p>
				</div>
			);
		}

		const { value, showResults } = this.state;

		if (value.length < 2 || !showResults) {
			return '';
		}

		const { results } = this.state;

		if (results.length) {
			return (
				<div className="wds-url-search-results">
					{results.map((item, index) =>
						this.renderSearchItem(item, index)
					)}
				</div>
			);
		}

		if (value[0] === '/') {
			return '';
		}

		return (
			<div className="wds-url-search-results">
				<p className="wds-url-search-no-result">
					{__('No results found', 'wds')}
				</p>
			</div>
		);
	}

	renderSearchItem(item, index) {
		return (
			<div
				key={index}
				className="wds-url-search-item"
				onClick={() => this.setSelected(item)}
			>
				<div className="wds-url-search-item-info">
					<div className="wds-url-search-item-title">
						{item.title || item.url}
					</div>
					{!!item.url && (
						<div className="wds-url-search-item-url">
							{item.url}
						</div>
					)}
				</div>
				<span className="wds-url-search-item-shortcut">
					{item.type}
				</span>
			</div>
		);
	}
}
