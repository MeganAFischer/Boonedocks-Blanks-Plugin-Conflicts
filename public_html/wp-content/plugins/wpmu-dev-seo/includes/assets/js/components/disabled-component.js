import React from 'react';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import Notice from './notices/notice';
import Button from './button';
import ConfigValues from '../es6/config-values';

const nonce = ConfigValues.get('settings_nonce', 'admin');
const isMember = ConfigValues.get('is_member', 'admin') === '1';

export default class DisabledComponent extends React.Component {
	static defaultProps = {
		imagePath: ConfigValues.get('empty_box_logo', 'admin') || false,
		message: '',
		notice: '',
		component: '',
		button: false,
		inner: false,
		premium: false,
		upgradeTag: '',
		nonceFields: true,
	};

	render() {
		const {
			imagePath,
			message,
			notice,
			component,
			button,
			inner,
			premium,
			upgradeTag,
			nonceFields,
		} = this.props;

		const referer = this.props.referer
			? this.props.referer
			: ConfigValues.get('referer', 'admin');

		return (
			<div
				className={classnames('sui-message', {
					'sui-box': !inner,
				})}
			>
				{!!imagePath && (
					<img
						src={imagePath}
						aria-hidden="true"
						className="wds-disabled-image"
						alt={__('Disabled component', 'wds')}
					/>
				)}
				<div className="sui-message-content">
					<p>{message}</p>

					{!!notice && <Notice message={notice}></Notice>}

					{premium && !isMember && (
						<Button
							color="purple"
							target="_blank"
							href={
								'https://wpmudev.com/project/smartcrawl-wordpress-seo/?utm_source=smartcrawl&utm_medium=plugin&utm_campaign=' +
								upgradeTag
							}
							text={__('Upgrade to Pro', 'wds')}
						></Button>
					)}

					{(!premium || isMember) && (
						<>
							{component && (
								<input
									type="hidden"
									name="wds-activate-component"
									value={component}
								/>
							)}
							{nonceFields && nonce && (
								<input
									type="hidden"
									id="_wds_nonce"
									name="_wds_nonce"
									value={nonce}
								/>
							)}
							{nonceFields && referer && (
								<input
									type="hidden"
									name="_wp_http_referer"
									value={referer}
								/>
							)}
							{button}
						</>
					)}
				</div>
			</div>
		);
	}
}
