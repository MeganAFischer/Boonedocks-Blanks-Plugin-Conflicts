import React from 'react';
import { __ } from '@wordpress/i18n';
import SettingsRow from '../../../components/settings-row';
import ConfigValues from '../../../es6/config-values';
import TextInputField from '../../../components/form-fields/text-input-field';

export default class Redirect extends React.Component {
	render() {
		const { redirect } = ConfigValues.get('advanced', 'metabox');

		if (!redirect || !redirect.has_permission) {
			return '';
		}

		return (
			<SettingsRow
				label={__('301 Redirect', 'wds')}
				description={__(
					'Send visitors to this URL to another page.',
					'wds'
				)}
			>
				<TextInputField
					id="wds_redirect"
					name="wds_redirect"
					description={__(
						'Enter the URL to send traffic to including http:// or https://',
						'wds'
					)}
					value={redirect.url ? redirect.url : ''}
				></TextInputField>
			</SettingsRow>
		);
	}
}
