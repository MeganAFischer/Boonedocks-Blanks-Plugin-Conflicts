import React from 'react';
import { __ } from '@wordpress/i18n';
import SubmoduleBox from '../../../components/layout/submodule-box';
import CodeType from './code-type';
import Previews from './previews';
import Separators from './separators';
import Configs from './configs';
import Formats from './formats';

export default class Breadcrumbs extends React.Component {
	render() {
		return (
			<SubmoduleBox
				name="breadcrumbs"
				title={__('Breadcrumbs', 'wds')}
				activateProps={{
					message: __(
						"Breadcrumbs provide an organized trail of links showing a visitor's journey on a website, improving the user experience and aiding search engines in understanding the site's structure for enhanced SEO.",
						'wds'
					),
				}}
				deactivateProps={{
					description: __(
						'No longer need breadcrumbs? This will deactivate this feature.',
						'wds'
					),
				}}
			>
				<CodeType />
				<Previews />
				<Separators />
				<Configs />
				<Formats />
			</SubmoduleBox>
		);
	}
}
