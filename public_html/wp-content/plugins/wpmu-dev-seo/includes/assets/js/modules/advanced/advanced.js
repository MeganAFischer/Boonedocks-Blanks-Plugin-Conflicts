import React from 'react';
import { __ } from '@wordpress/i18n';
import Autolinks from './autolinks/autolinks';
import Redirects from './redirects/redirects';
import Woocommerce from './woocommerce/woocommerce';
import Breadcrumbs from './breadcrumbs/breadcrumbs';
import RequestUtil from '../../utils/request-util';
import ConfigValues from '../../es6/config-values';
import Seomoz from './seomoz/seomoz';
import Robots from './robots/robots';
import ModuleProvider from '../../components/layout/module-provider';

export default class Advanced extends React.Component {
	componentDidMount() {
		if (parseInt(ConfigValues.get('new_feature_status', 'admin')) > 0) {
			return;
		}

		RequestUtil.post(
			'smartcrawl_new_feature_status',
			ConfigValues.get('nonce', 'admin'),
			{ step: 1 }
		);
	}

	render() {
		return (
			<ModuleProvider
				name="advanced"
				title={__('Advanced Tools', 'wds')}
				docChapter="advanced-tools"
				utmCampaign="smartcrawl_advanced-tools_docs"
				submodules={{
					autolinks: <Autolinks />,
					redirects: <Redirects />,
					woocommerce: <Woocommerce />,
					breadcrumbs: <Breadcrumbs />,
					seomoz: <Seomoz />,
					robots: <Robots />,
				}}
			/>
		);
	}
}
