import React from 'react';
import { __ } from '@wordpress/i18n';
import SubmoduleBox from '../../../components/layout/submodule-box';
import Settings from './settings';

export default class Woocommerce extends React.Component {
	render() {
		return (
			<SubmoduleBox
				name="woocommerce"
				title={__('WooCommerce SEO', 'wds')}
				activateProps={{
					message: __(
						'Activate WooCommerce SEO to add the required metadata and Product Schema to your WooCommerce site, helping you stand out in search results.',
						'wds'
					),
				}}
			>
				<Settings />
			</SubmoduleBox>
		);
	}
}
