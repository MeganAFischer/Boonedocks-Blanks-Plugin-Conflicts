import React from 'react';
import { __ } from '@wordpress/i18n';
import SeoAnalysisCheckItem from '../../seo-analysis-check-item';

export default class SeoAnalysisCheckTitleKeywords extends React.Component {
	static defaultProps = {
		data: {},
		onIgnore: () => false,
		onUnignore: () => false,
	};

	render() {
		const { data, onIgnore, onUnignore } = this.props;

		return (
			<SeoAnalysisCheckItem
				id="title-keywords"
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
							"You've got your focus keyphrase(s) in the SEO title meaning it has the best chance of matching what users are searching for first up - nice work.",
							'wds'
					  )
					: __(
							"The focus keyphrase(s) for this article doesn't appear in the SEO title which means it has less of a chance of matching what your visitors will search for.",
							'wds'
					  )}
			</p>
		);
	}

	getStatusMessage() {
		const { state } = this.props.data.result;

		return -1 === state
			? __(
					"We couldn't find a title to check for keyphrases",
					'wds'
			  )
			: state === false
			? __(
					"Your focus keyphrase(s) aren't used in the SEO title",
					'wds'
			  )
			: __(
					'The SEO title contains your focus keyphrase(s)',
					'wds'
			  );
	}

	getMoreInfo() {
		return (
			<p>
				{__(
					"It's considered good practice to try to include your focus keyphrase(s) in the SEO title of a page because this is what people looking for the article are likely searching for. The higher chance of a keyphrase match, the greater the chance that your article will be found higher up in search results. Whilst it's recommended to try and get these words in, don't sacrifice readability and the quality of the SEO title just to rank higher - people may not want to click on it if it doesn't read well.",
					'wds'
				)}
			</p>
		);
	}
}
