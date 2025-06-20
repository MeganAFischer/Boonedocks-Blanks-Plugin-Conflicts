import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import classnames from 'classnames';

export default class Toggle extends React.Component {
	static defaultProps = {
		label: '',
		labelPlacement: 'end', // enum values: start, end
		tooltip: '',
		description: '',
		inverted: false,
		wrapped: false,
		wrapperClass: '',
		fullWidth: false,
		onChange: () => false,
	};

	constructor(props) {
		super(props);

		this.state = { checked: this.props.checked || false };
	}

	handleChange(e) {
		this.props.onChange(e.target.checked, e.target);

		this.setState({ checked: e.target.checked });
	}

	render() {
		if (this.props.wrapped) {
			return (
				<div
					className={classnames(
						'sui-toggle-wrapper',
						this.props.wrapperClass,
						this.props.fullWidth && 'sui-toggle-wrapper-full'
					)}
				>
					{this.inner()}
				</div>
			);
		}
		return this.inner();
	}

	inner() {
		const {
			label,
			labelPlacement,
			tooltip,
			description,
			children,
			inverted,
			fullWidth,
			className,
		} = this.props;

		const { checked } = this.state;

		const validProps = ['id', 'name', 'disabled'];
		const inputProps = Object.keys(this.props)
			.filter((propName) => validProps.includes(propName))
			.reduce((obj, propName) => {
				obj[propName] = this.props[propName];
				return obj;
			}, {});

		const hasChildren = (inverted ? !checked : checked) && children;

		return (
			<>
				<label
					className={classnames(
						className,
						'sui-toggle',
						labelPlacement !== 'end' &&
							`sui-toggle-label-${labelPlacement}`,
						{
							'sui-toggle-full': fullWidth,
							'sui-toggle-inverted': inverted,
						}
					)}
				>
					<input
						type="checkbox"
						checked={checked}
						onChange={(e) => this.handleChange(e)}
						{...inputProps}
					/>

					<span className="sui-toggle-slider" aria-hidden="true" />

					{!!label && (
						<span
							className={classnames('sui-toggle-label', {
								'sui-tooltip sui-tooltip-constrained': tooltip,
							})}
							data-tooltip={tooltip ? tooltip : undefined}
							style={
								tooltip
									? { '--tooltip-width': '240px' }
									: undefined
							}
						>
							{label}
						</span>
					)}

					{!!description && (
						<span className="sui-description">{description}</span>
					)}
				</label>
				{!!hasChildren && (
					<div
						className={classnames(
							'sui-toggle-content',
							'sui-border-frame'
						)}
						aria-label={sprintf(
							/* translators: %s: toggle label. */
							__("Children of '%s'", 'wds-texdomain'),
							label
						)}
					>
						{children}
					</div>
				)}
			</>
		);
	}
}
