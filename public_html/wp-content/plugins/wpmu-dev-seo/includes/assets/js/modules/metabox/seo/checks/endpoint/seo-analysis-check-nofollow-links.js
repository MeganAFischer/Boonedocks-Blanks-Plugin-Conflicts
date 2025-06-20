import React from 'react';
import { __ } from '@wordpress/i18n';
import SeoAnalysisCheckItem from '../../seo-analysis-check-item';

export default class SeoAnalysisCheckNofollowLinks extends React.Component {
	static defaultProps = {
		data: {},
		onIgnore: () => false,
		onUnignore: () => false,
	};

	render() {
		const { data, onIgnore, onUnignore } = this.props;

		return (
			<SeoAnalysisCheckItem
				id="nofollow-links"
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
		const { state } = this.props.data.result;

		return (
			<p>
				{state
					? __(
							'At least one dofollow external link was found on the content of this page. Good job!',
							'wds'
					  )
					: __(
							'We detected that all external links on this page are nofollow links. We recommend adding at least one external dofollow link to your content.',
							'wds'
					  )}
			</p>
		);
	}

	getStatusMessage() {
		const { state } = this.props.data.result;

		return state
			? __('A dofollow external link(s) was found', 'wds')
			: __('Nofollow external links', 'wds');
	}

	getMoreInfo() {
		return (
			<p>
				{__(
					'It might feel absurd to link to external web pages as it will redirect your traffic to another site. However, adding relevant outbound links helps improve your credibility, gives your user more value, and helps search engines determine the usefulness and quality of your content.',
					'wds'
				)}
			</p>
		);
	}
}
