import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import ConfigValues from '../../es6/config-values';
import SocialItem from './social/social-item';

export default class MetaboxSocial extends React.Component {
	render() {
		const tagOnpageUrl = ConfigValues.get(`tab_onpage_url`, 'metabox');
		const og = ConfigValues.get('opengraph', 'metabox');
		const twt = ConfigValues.get('twitter', 'metabox');

		return (
			<div className="wds_social">
				<div className="wds-metabox-section sui-box-body">
					<p>
						{createInterpolateElement(
							sprintf(
								// translators: %s: plugin title
								__(
									"Customize this post's title, description and featured images for social shares. You can also configure the default settings for this post type in <strong>%s</strong>'s <a>Titles & Meta</a> area.",
									'wds'
								),
								ConfigValues.get('plugin_title', 'admin')
							),
							{ a: <a href={tagOnpageUrl} />, strong: <strong /> }
						)}
					</p>
					<SocialItem
						label={__('OpenGraph', 'wds')}
						description={__(
							'OpenGraph is used on many social networks such as Facebook.',
							'wds'
						)}
						titlePlaceholder={og.title_placeholder}
						titleValue={og.title_value}
						descPlaceholder={og.desc_placeholder}
						descValue={og.desc_value}
						disabled={og.disabled}
						images={og.images}
						isSingle={false}
					></SocialItem>
					<SocialItem
						label={__('Twitter', 'wds')}
						description={__(
							'These details will be used in Twitter cards.',
							'wds'
						)}
						type="twitter"
						titlePlaceholder={twt.title_placeholder}
						titleValue={twt.title_value}
						descPlaceholder={twt.desc_placeholder}
						descValue={twt.desc_value}
						disabled={twt.disabled}
						images={twt.images}
						isSingle={true}
					></SocialItem>
				</div>
			</div>
		);
	}
}
