import React from 'react';
import { __ } from '@wordpress/i18n';
import SettingsRow from '../../../components/settings-row';
import ConfigValues from '../../../es6/config-values';
import TextInputField from '../../../components/form-fields/text-input-field';

export default class Canonical extends React.Component {
	render() {
		const { canonical } = ConfigValues.get('advanced', 'metabox');

		return (
			<SettingsRow
				label={__('Canonical', 'wds')}
				description={__(
					'If you have several similar versions of this page you can point search engines to the canonical or "genuine" version to avoid duplicate content issues.',
					'wds'
				)}
			>
				<TextInputField
					id="wds_canonical"
					name="wds_canonical"
					description={__(
						'Enter the full canonical URL including http:// or https://',
						'wds'
					)}
					value={canonical.url ? canonical.url : ''}
				></TextInputField>
			</SettingsRow>
		);
	}
}
