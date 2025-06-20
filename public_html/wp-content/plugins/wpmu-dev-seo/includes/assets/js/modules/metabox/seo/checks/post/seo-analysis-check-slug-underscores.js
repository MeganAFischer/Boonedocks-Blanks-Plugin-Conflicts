import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import SeoAnalysisCheckItem from '../../seo-analysis-check-item';
import { createInterpolateElement } from '@wordpress/element';

export default class SeoAnalysisCheckSlugUnderscores extends React.Component {
	static defaultProps = {
		data: {},
		onIgnore: () => false,
		onUnignore: () => false,
	};

	render() {
		const { data, onIgnore, onUnignore } = this.props;

		return (
			<SeoAnalysisCheckItem
				id="slug-underscores"
				ignored={data.ignored}
				status={data.status}
				recommendation={this.getRecommendation()}
				statusMsg={this.getStatusMessage()}
				moreInfo={this.getMoreInfo()}
				onIgnore={onIgnore}
				onUnignore={onUnignore}
			/>
		);
	}

	getRecommendation() {
		const { state, mark_up: mark } = this.props.data.result;
		return (
			<p>
				{!state
					? createInterpolateElement(
							sprintf(
								// translators: %s current post url.
								__(
									"We have detected one or more underscores in the URL {%s}. Please consider removing them or replacing them with a hyphen (-). However, if you have already published this page, we don\\'t recommend removing the underscores (_) as it can cause short-term ranking loss. If you decide to remove the underscores in the URL of a published page, set up a 301 Redirect using the URL Redirection tool to direct traffic to the new URL. Learn more about <strong>URL Redirection</strong> on our <a>documentation</a>.",
									'wds'
								),
								mark
							),
							{
								a: (
									<a
										href="https://wpmudev.com/docs/wpmu-dev-plugins/smartcrawl/#url-redirection"
										target="_blank"
										rel="noreferrer"
									/>
								),
								strong: <strong />,
							}
					  )
					: sprintf(
							// translators: %s current post url.
							__(
								"We didn't detect underscores in your page URL {%s}. Good job!",
								'wds'
							),
							mark
					  )}
			</p>
		);
	}

	getStatusMessage() {
		const { state } = this.props.data.result;

		return !state
			? __('Your URL contains underscores', 'wds')
			: __("Your URL doesn't contain underscores", 'wds');
	}

	getMoreInfo() {
		return (
			<p>
				{__(
					'Google recommends using hyphens to separate words in the URLs instead of underscores, which helps search engines easily identify the page topic.',
					'wds'
				)}
			</p>
		);
	}
}
