import React from 'react';

export default class NonceField extends React.Component {
	static defaultProps = {
		nonce: '',
		name: '_wpnonce',
		referer: true,
	};

	render() {
		const { nonce, name, referer } = this.props;

		return (
			<React.Fragment>
				{!!nonce && (
					<input type="hidden" id={name} name={name} value={nonce} />
				)}

				{!!referer && (
					<input
						type="hidden"
						name="_wp_http_referer"
						value={referer}
					/>
				)}
			</React.Fragment>
		);
	}
}
