import React from 'react';
import { __ } from '@wordpress/i18n';
import SeoAnalysisCheckItem from '../../seo-analysis-check-item';

export default class SeoAnalysisCheckMetadescKeywords extends React.Component {
	static defaultProps = {
		data: {},
		onIgnore: () => false,
		onUnignore: () => false,
	};

	render() {
		const { data, onIgnore, onUnignore } = this.props;

		return (
			<SeoAnalysisCheckItem
				id="metadesc-keywords"
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
							'The focus keyphrase for this article appears in the SEO description which means it has a better chance of matching what your visitors will search for, brilliant!',
							'wds'
					  )
					: __(
							"An SEO description without your focus keyphrases has less chance of matching what your visitors are searching for, versus a description that does. It's worth trying to get your focus keyphrases in there, just remember to keep it readable and natural.",
							'wds'
					  )}
			</p>
		);
	}

	getStatusMessage() {
		const { state } = this.props.data.result;

		return -1 === state
			? __(
					"We couldn't find a description to check for keyphrases",
					'wds'
			  )
			: false === state
			? __(
					"The SEO description doesn't contain your focus keyphrases",
					'wds'
			  )
			: __(
					'The SEO description contains your focus keyphrases',
					'wds'
			  );
	}

	getMoreInfo() {
		return (
			<p>
				{__(
					"It's considered good practice to try to include your focus keyphrase(s) in the SEO description of your pages, because this is what people looking for the article are likely searching for. The higher chance of a keyphrase match, the higher chance your article will be found higher up in search results. Remember this is your chance to give a potential visitor a quick peek into what's inside your article. If they like what they read they'll click on your link.",
					'wds'
				)}
			</p>
		);
	}
}
