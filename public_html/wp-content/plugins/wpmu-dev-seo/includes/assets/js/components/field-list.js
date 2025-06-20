import React from 'react';

export default class FieldList extends React.Component {
	static defaultProps = {
		title: '',
		description: '',
		items: [],
	};

	render() {
		const { title, description, items } = this.props;

		return (
			<div className="sui-field-list">
				{(!!title || !!description) && (
					<div className="sui-field-list-header">
						{!!title && (
							<h3 className="sui-field-list-title">{title}</h3>
						)}
						{!!description && (
							<p
								id="link-to-description"
								className="sui-description"
							>
								{description}
							</p>
						)}
					</div>
				)}
				{items && (
					<div className="sui-field-list-body">
						{items.map((item, index) => (
							<div key={index} className="sui-field-list-item">
								{item}
							</div>
						))}
					</div>
				)}
			</div>
		);
	}
}
