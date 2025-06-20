import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import CrawlItemGroup from './crawl-item-group';
import ConfigValues from '../../es6/config-values';

export default class CrawlItemGroupSitemap extends React.Component {
	render() {
		return (
			<CrawlItemGroup
				{...this.props}
				singularTitle={
					// translators: %s: Number of active items.
					__('%s URL is missing from the sitemap', 'wds')
				}
				pluralTitle={
					// translators: %s: Number of active items.
					__('%s URLs are missing from the sitemap', 'wds')
				}
				description={createInterpolateElement(
					sprintf(
						// translators: %s: plugin title
						__(
							"%s couldn't find these URLs in your Sitemap. You can choose to add them to your Sitemap, or ignore the warning if you don’t want them included.",
							'wds'
						),
						ConfigValues.get('plugin_title', 'admin')
					),
					{ strong: <strong /> }
				)}
				warningClass="sui-default"
			/>
		);
	}
}
