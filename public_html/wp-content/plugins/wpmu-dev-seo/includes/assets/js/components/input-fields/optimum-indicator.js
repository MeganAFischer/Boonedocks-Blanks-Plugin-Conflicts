import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import classnames from 'classnames';
import StringUtils from '../../es6/string-utils';

export default class OptimumIndicator extends React.Component {
	static defaultProps = {
		value: '',
		placeholder: '',
		preview: '',
		lower: 0,
		upper: Infinity,
		control: false,
	};

	render() {
		const { value, preview, placeholder } = this.props;
		const lower = parseInt(this.props.lower);
		const upper = parseInt(this.props.upper);

		const idealLen = (lower + upper) / 2;
		const length = StringUtils.normalize_whitespace(
			StringUtils.strip_html(value ? preview || value : placeholder)
		).length;
		const offset = (8 / 100) * upper;
		const almostLower = lower + offset,
			almostUpper = upper - offset;

		let percentage = (length / idealLen) * 100;
		percentage = percentage / 2;
		percentage = percentage > 100 ? 100 : percentage;

		const Control = this.props.control;
		const controlProps = Object.assign({}, this.props);

		delete controlProps.preview;
		delete controlProps.lower;
		delete controlProps.upper;
		delete controlProps.label;
		delete controlProps.control;

		return (
			<div className="wds-optimum-indicator-wrapper">
				<Control {...controlProps} />

				<div
					className={classnames(
						'wds-optimum-indicator',
						length > upper
							? 'over'
							: almostUpper < length && length <= upper
							? 'almost-over'
							: almostLower <= length && length <= almostUpper
							? 'just-right'
							: lower <= length && length < almostLower
							? 'almost-under'
							: 'under'
					)}
				>
					<span style={{ width: percentage + '%' }}></span>
					<span>
						{sprintf(
							/* translators: 1: current, 2: lower, 3: upper values */
							__('%1$d / %2$d-%3$d characters', 'wds'),
							length,
							lower,
							upper
						)}
					</span>
				</div>
			</div>
		);
	}
}
