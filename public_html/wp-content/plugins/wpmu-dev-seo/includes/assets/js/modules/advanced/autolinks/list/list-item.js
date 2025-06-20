import React from 'react';
import Button from '../../../../components/button';

export default class PostlistItem extends React.Component {
	static defaultProps = {
		label: '',
		typeLabel: '',
		onRemove: () => false,
	};

	render() {
		const { label, typeLabel, onRemove } = this.props;

		return (
			<tr>
				<td>
					<strong>{label}</strong>
				</td>
				<td>{typeLabel}</td>
				<td className="wds-postlist-item-remove">
					<Button
						color="red"
						icon="sui-icon-trash"
						onClick={() => onRemove()}
					/>
				</td>
			</tr>
		);
	}
}
