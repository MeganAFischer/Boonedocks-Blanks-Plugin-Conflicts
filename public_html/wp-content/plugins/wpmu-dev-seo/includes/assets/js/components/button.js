import React from 'react';
import classnames from 'classnames';

export default class Button extends React.Component {
	static defaultProps = {
		id: '',
		name: '',
		text: '',
		color: '',
		dashed: false,
		icon: false,
		iconRight: false,
		loading: false,
		ghost: false,
		disabled: false,
		href: '',
		target: '',
		className: '',
		value: '',
	};

	handleClick(e) {
		e.preventDefault();

		this.props.onClick(e);
	}

	render() {
		const {
			id,
			name,
			type,
			href,
			target,
			disabled,
			text,
			tooltip,
			className,
			color,
			iconRight,
			loading,
			ghost,
			dashed,
			onClick,
			value,
		} = this.props;

		let HtmlTag, props;

		if (href) {
			HtmlTag = 'a';
			props = { href };

			if (target) {
				props.target = target;
			}
		} else {
			HtmlTag = 'button';
			props = {
				disabled,
			};

			if (onClick) {
				props.onClick = (e) => this.handleClick(e);
			}

			if (type) {
				props.type = type;
			}
		}

		if (id) {
			props.id = id;
		}

		if (name) {
			props.name = name;
		}

		const hasText = text && text.trim();

		if (tooltip) {
			props['data-tooltip'] = tooltip;
		}

		if (typeof value !== 'undefined') {
			props.value = value;
		}

		return (
			<>
				<HtmlTag
					{...props}
					className={classnames(
						className,
						color ? 'sui-button-' + color : '',
						{
							'sui-button-onload': loading,
							'sui-button-ghost': ghost,
							'sui-button-dashed': dashed,
							'sui-tooltip': !!tooltip,
							'sui-button-icon': !hasText,
							'sui-button': hasText,
							'sui-button-icon-right': iconRight,
						}
					)}
				>
					{this.text()}
					{this.loadingIcon()}
				</HtmlTag>
			</>
		);
	}

	text() {
		const { text, icon, iconRight, loading } = this.props;

		const iconHtml = icon ? (
			<span className={icon} aria-hidden="true" />
		) : (
			''
		);

		const props = {};

		if (loading) {
			props.className = classnames({
				'sui-loading-text': loading,
			});
		}

		if (iconRight) {
			return (
				<span {...props}>
					{text}
					{iconHtml}
				</span>
			);
		}
		return (
			<span {...props}>
				{iconHtml} {text}
			</span>
		);
	}

	loadingIcon() {
		return this.props.loading ? (
			<span className="sui-icon-loader sui-loading" aria-hidden="true" />
		) : (
			''
		);
	}
}
