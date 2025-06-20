import React from 'react';
import ConfigValues from '../../../es6/config-values';
import Toggle from '../../../components/toggle';
import Checkbox from '../../../components/checkbox';
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import Notice from '../../../components/notices/notice';

const tooltips = {
	autolinks: createInterpolateElement(
		sprintf(
			// translators: %s: plugin title
			__(
				'<strong>%s</strong> will look for keywords that match posts/pages around your website and automatically link them.',
				'wds'
			),
			ConfigValues.get('plugin_title', 'admin')
		),
		{ strong: <strong /> }
	),
	redirects: __(
		'Automatically redirect traffic from one URL to another. Use this tool if you have changed a page’s URL and wish to keep traffic flowing to the new page.',
		'wds'
	),
	woocommerce: __(
		'Add recommended Woo Meta and Product schema to your WooCommerce site.',
		'wds'
	),
	seomoz: __(
		'Moz provides reports that tell you how your site stacks up against the competition with all of the important SEO measurement tools etc.',
		'wds'
	),
	robots: __(
		'A robots.txt file tells bots what to index on your site and where they are.',
		'wds'
	),
	breadcrumbs: __(
		"Add breadcrumb trails to your web pages to indicate the page's position in the site hierarchy and help users understand and explore your site effectively.",
		'wds'
	),
};
const title = ConfigValues.get('title', 'advanced'),
	submodules = ConfigValues.get('submodules', 'advanced') || [],
	isMember = ConfigValues.get('is_member', 'admin');

export default class PluginModules extends React.Component {
	render() {
		return (
			<>
				{Object.keys(submodules).length > 0 && (
					<div className="wds-module">
						<div className="wds-module-header">
							<span className="wds-module-title">{title}</span>
						</div>
						<div className="wds-module-body">
							{Object.keys(submodules).map((key, index) => (
								<div className="sui-form-field" key={index}>
									<Toggle
										name={`wds_settings_options[advanced][${key}]`}
										label={
											<>
												{submodules[key]?.title}{' '}
												{submodules[key]?.premium &&
													!isMember && (
														<span
															className="sui-tag sui-tag-pro sui-tooltip"
															data-tooltip="Upgrade to SmartCrawl Pro"
														>
															{__('Pro', 'wds')}
														</span>
													)}
											</>
										}
										tooltip={tooltips[key]}
										checked={
											!submodules[key]?.warning &&
											submodules[key]?.active &&
											(!submodules[key]?.premium ||
												isMember)
										}
										disabled={
											submodules[key]?.warning ||
											(submodules[key]?.premium &&
												!isMember)
										}
									/>
									{submodules[key].warning && (
										<Notice
											type="warning"
											message={
												<span
													dangerouslySetInnerHTML={{
														__html: submodules[key]
															.warning,
													}}
												></span>
											}
										/>
									)}
								</div>
							))}
						</div>
					</div>
				)}

				<hr />

				<div className="sui-form-field">
					<Checkbox
						name={`wds_settings_options[hide_disables]`}
						label={__(
							'Hide disabled modules from the Dashboard and sub-menu',
							'wds'
						)}
						defaultChecked={ConfigValues.get(
							'hide_disables',
							'settings'
						)}
					></Checkbox>
				</div>
			</>
		);
	}
}
