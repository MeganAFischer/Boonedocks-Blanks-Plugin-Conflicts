import React from 'react';
import { __ } from '@wordpress/i18n';
import Indexing from './advanced/indexing';
import Canonical from './advanced/canonical';
import Redirect from './advanced/redirect';
import AutoLinking from './advanced/auto-linking';

export default class MetaboxAdvanced extends React.Component {
	render() {
		return (
			<div className="wds_advanced">
				<div className="wds-metabox-section sui-box-body">
					<p>
						{__(
							'Configure the advanced settings for this post.',
							'wds'
						)}
					</p>

					<Indexing></Indexing>
					<Canonical></Canonical>
					<Redirect></Redirect>
					<AutoLinking></AutoLinking>
				</div>
			</div>
		);
	}
}
