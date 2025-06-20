import React from 'react';
import { __ } from '@wordpress/i18n';
import ConfigValues from '../../es6/config-values';

const isMember = ConfigValues.get('is_member', 'admin') === '1';

export default class Footer extends React.Component {
	render() {
		return (
			<>
				<div
					className="sui-footer"
					dangerouslySetInnerHTML={{
						__html: ConfigValues.get('footer_text', 'admin'),
					}}
				/>

				<ul className="sui-footer-nav">
					{isMember ? (
						<>
							<li>
								<a
									href="https://wpmudev.com/hub2/"
									target="_blank"
									rel="noreferrer"
								>
									{__('The Hub', 'wds')}
								</a>
							</li>
							<li>
								<a
									href="https://wpmudev.com/projects/category/plugins/"
									target="_blank"
									rel="noreferrer"
								>
									{__('Plugins', 'wds')}
								</a>
							</li>
							<li>
								<a
									href="https://wpmudev.com/roadmap/"
									target="_blank"
									rel="noreferrer"
								>
									{__('Roadmap', 'wds')}
								</a>
							</li>
							<li>
								<a
									href="https://wpmudev.com/hub2/support"
									target="_blank"
									rel="noreferrer"
								>
									{__('Support', 'wds')}
								</a>
							</li>
							<li>
								<a
									href="https://wpmudev.com/docs/"
									target="_blank"
									rel="noreferrer"
								>
									{__('Docs', 'wds')}
								</a>
							</li>
							<li>
								<a
									href="https://wpmudev.com/hub2/community/"
									target="_blank"
									rel="noreferrer"
								>
									{__('Community', 'wds')}
								</a>
							</li>
							<li>
								<a
									href="https://wpmudev.com/terms-of-service/"
									target="_blank"
									rel="noreferrer"
								>
									{__('Terms of Service', 'wds')}
								</a>
							</li>
							<li>
								<a
									href="https://incsub.com/privacy-policy/"
									target="_blank"
									rel="noreferrer"
								>
									{__('Privacy Policy', 'wds')}
								</a>
							</li>
						</>
					) : (
						<>
							<li>
								<a
									href="https://profiles.wordpress.org/wpmudev#content-plugins"
									target="_blank"
									rel="noreferrer"
								>
									{__('Free Plugins', 'wds')}
								</a>
							</li>
							<li>
								<a
									href="https://wpmudev.com/features/"
									target="_blank"
									rel="noreferrer"
								>
									{__('Membership', 'wds')}
								</a>
							</li>
							<li>
								<a
									href="https://wpmudev.com/roadmap/"
									target="_blank"
									rel="noreferrer"
								>
									{__('Roadmap', 'wds')}
								</a>
							</li>
							<li>
								<a
									href="https://wordpress.org/support/plugin/smartcrawl-seo"
									target="_blank"
									rel="noreferrer"
								>
									{__('Support', 'wds')}
								</a>
							</li>
							<li>
								<a
									href="https://wpmudev.com/docs/"
									target="_blank"
									rel="noreferrer"
								>
									{__('Docs', 'wds')}
								</a>
							</li>
							<li>
								<a
									href="https://wpmudev.com/hub-welcome/"
									target="_blank"
									rel="noreferrer"
								>
									{__('The Hub', 'wds')}
								</a>
							</li>
							<li>
								<a
									href="https://wpmudev.com/terms-of-service/"
									target="_blank"
									rel="noreferrer"
								>
									{__('Terms of Service', 'wds')}
								</a>
							</li>
							<li>
								<a
									href="https://incsub.com/privacy-policy/"
									target="_blank"
									rel="noreferrer"
								>
									{__('Privacy Policy', 'wds')}
								</a>
							</li>
						</>
					)}
				</ul>

				<ul className="sui-footer-social">
					<li>
						<a
							href="https://www.facebook.com/wpmudev"
							target="_blank"
							rel="noreferrer"
						>
							<span
								className="sui-icon-social-facebook"
								aria-hidden="true"
							></span>
							<span className="sui-screen-reader-text">
								{__('Facebook', 'wds')}
							</span>
						</a>
					</li>
					<li>
						<a
							href="https://twitter.com/wpmudev"
							target="_blank"
							rel="noreferrer"
						>
							<span
								className="sui-icon-social-twitter"
								aria-hidden="true"
							></span>
							<span className="sui-screen-reader-text">
								{__('Twitter', 'wds')}
							</span>
						</a>
					</li>
					<li>
						<a
							href="https://www.instagram.com/wpmu_dev/"
							target="_blank"
							rel="noreferrer"
						>
							<span
								className="sui-icon-instagram"
								aria-hidden="true"
							></span>
							<span className="sui-screen-reader-text">
								{__('Instagram', 'wds')}
							</span>
						</a>
					</li>
				</ul>
			</>
		);
	}
}
