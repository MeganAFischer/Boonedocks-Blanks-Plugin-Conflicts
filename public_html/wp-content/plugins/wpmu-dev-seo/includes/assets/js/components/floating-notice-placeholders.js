import React from 'react';

export default class FloatingNoticePlaceholders extends React.Component {
	static defaultProps = {
		ids: '',
	};

	render() {
		return (
			<div className="sui-floating-notices">
				{this.props.ids.map((id, index) => (
					<div
						key={index}
						role="alert"
						id={id}
						className="sui-notice"
						aria-live="assertive"
					></div>
				))}
			</div>
		);
	}
}
