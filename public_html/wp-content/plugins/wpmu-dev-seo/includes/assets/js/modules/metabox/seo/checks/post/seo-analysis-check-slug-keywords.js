import React from 'react';
import { __ } from '@wordpress/i18n';
import SeoAnalysisCheckItem from '../../seo-analysis-check-item';

export default class SeoAnalysisCheckSlugKeywords extends React.Component {
	static defaultProps = {
		data: {},
		onIgnore: () => false,
		onUnignore: () => false,
	};

	render() {
		const { data, onIgnore, onUnignore } = this.props;

		return (
			<SeoAnalysisCheckItem
				id="slug-keywords"
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
							"You've got your focus keyphrases in the page slug which can help your page rank as you have a higher chance of matching search terms, and Google does index your page URL, great stuff!",
							'wds'
					  )
					: __(
							'Google does index your page URL. Using your focus keyphrases in the page slug can help your page rank as you have a higher chance of matching search terms. Try getting your focus keyphrases in there.',
							'wds'
					  )}
			</p>
		);
	}

	getStatusMessage() {
		const { state } = this.props.data.result;

		return !state
			? __(
					"You haven't used your focus keyphrases in the page URL",
					'wds'
			  )
			: __(
					"You've used your focus keyphrase in the page URL",
					'wds'
			  );
	}

	getMoreInfo() {
		return (
			<p>
				{__(
					"The page URL you use for this post will be visible in search engine results, so it's important to also include words that the searcher is looking for (your focus keyphrases). It's debatable whether keyphrases in the slug are of any real search engine ranking benefit. One could assume that because the slug does get indexed, the algorithm may favour slugs more closely aligned with the topic being searched.",
					'wds'
				)}
			</p>
		);
	}
}
