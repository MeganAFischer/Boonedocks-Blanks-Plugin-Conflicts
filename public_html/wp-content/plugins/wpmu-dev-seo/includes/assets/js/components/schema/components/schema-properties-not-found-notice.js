import React from 'react';
import { __ } from '@wordpress/i18n';

export class SchemaPropertiesNotFoundNotice extends React.Component {
	render() {
		return (
			<tr>
				<td colSpan={4}>
					<span>
						{__(
							'You haven’t added any properties yet. Click on the add property button below to add one.',
							'wds'
						)}
					</span>
				</td>
			</tr>
		);
	}
}
