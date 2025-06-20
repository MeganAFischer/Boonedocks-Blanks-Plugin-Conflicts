import React from 'react';

export default class LighthouseTag extends React.Component {
	static defaultProps = {
		tag: '',
		attributes: {},
		selfClosing: true,
		doctype: false,
	};

	render() {
		const { tag, attributes, selfClosing, children } = this.props;

		if (tag === 'doctype') {
			return <span className="wds-lh-tag">{'<!DOCTYPE html>'}</span>;
		}

		return (
			<React.Fragment>
				<span className="wds-lh-tag">{`<${tag}`}</span>
				{Object.keys(attributes).map((attr, index) => (
					<React.Fragment key={index}>
						&nbsp;
						<span className="wds-lh-attr">{attr}=&#34;</span>
						{attributes[attr]}
						<span className="wds-lh-attr">&#34;</span>
					</React.Fragment>
				))}
				<span className="wds-lh-tag">
					{!!children || !selfClosing ? '>' : '/>'}
				</span>
				{children}
				{children && <span className="wds-lh-tag">{`</${tag}>`}</span>}
			</React.Fragment>
		);
	}
}
