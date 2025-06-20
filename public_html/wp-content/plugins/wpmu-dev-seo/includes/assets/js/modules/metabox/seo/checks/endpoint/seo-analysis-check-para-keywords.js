import React from 'react';
import { __ } from '@wordpress/i18n';
import SeoAnalysisCheckItem from '../../seo-analysis-check-item';

export default class SeoAnalysisCheckParaKeywords extends React.Component {
	static defaultProps = {
		data: {},
		onIgnore: () => false,
		onUnignore: () => false,
	};

	render() {
		const { data, onIgnore, onUnignore } = this.props;

		return (
			<SeoAnalysisCheckItem
				id="para-keywords"
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
							"You've included your focus keyphrases in the first paragraph of your content, which will help search engines and visitors quickly scope the topic of your article. Well done!",
							'wds'
					  )
					: __(
							"It's good practice to include your focus keyphrases in the first paragraph of your content so that search engines and visitors can quickly scope the topic of your article.",
							'wds'
					  )}
			</p>
		);
	}

	getStatusMessage() {
		const { state } = this.props.data.result;

		return state
			? __(
					'The focus keyphrase appears in the first paragraph of your article',
					'wds'
			  )
			: __(
					"You haven't included the focus keyphrases in the first paragraph of your article",
					'wds'
			  );
	}

	getMoreInfo() {
		return (
			<p>
				{__(
					'You should clearly formulate what your post is about in the first paragraph. In printed texts, a writer usually starts off with some kind of teaser, but there is no time for that if you are writing for the web. You only have seconds to gain your reader’s attention. Make sure the first paragraph tells the main message of your post. That way, you make it easy for your reader to figure out what your post is about. Doing this also tells Google what your post is about. Don’t forget to put your focus keyphrase in that first paragraph!',
					'wds'
				)}
			</p>
		);
	}
}
