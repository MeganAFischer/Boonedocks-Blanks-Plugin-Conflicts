import React from 'react';
import { __ } from '@wordpress/i18n';
import SeoAnalysisCheckItem from '../../seo-analysis-check-item';

export default class SeoAnalysisCheckTitleSecondaryKeywords extends React.Component {
	static defaultProps = {
		data: {},
		onIgnore: () => false,
		onUnignore: () => false,
	};

	render() {
		const { data, onIgnore, onUnignore } = this.props;

		return (
			<SeoAnalysisCheckItem
				id="title-secondary-keywords"
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
		return (
			<p>
				{__(
					"It's recommended to use your secondary keyphrases in the title of your page if possible. However, it has a minor impact on improving SEO.",
					'wds'
				)}
			</p>
		);
	}

	getStatusMessage() {
		const { state } = this.props.data.result;

		return state === -1
			? __(
					"We couldn't find a title to check for keyphrases",
					'wds'
			  )
			: !state
			? __(
					"You didn't use this secondary keyphrase in the title.",
					'wds'
			  )
			: __(
					'You have used this secondary keyphrase in the title.',
					'wds'
			  );
	}

	getMoreInfo() {
		return (
			<p>
				{__(
					'Selecting focus keyphrases helps describe what your content is about.',
					'wds'
				)}
			</p>
		);
	}
}
