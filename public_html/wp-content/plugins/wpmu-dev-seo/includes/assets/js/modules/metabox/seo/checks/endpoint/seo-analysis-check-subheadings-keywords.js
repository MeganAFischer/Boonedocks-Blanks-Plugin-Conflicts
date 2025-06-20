import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import SeoAnalysisCheckItem from '../../seo-analysis-check-item';

export default class SeoAnalysisCheckSubheadingsKeywords extends React.Component {
	static defaultProps = {
		data: {},
		onIgnore: () => false,
		onUnignore: () => false,
	};

	render() {
		const { data, onIgnore, onUnignore } = this.props;

		return (
			<SeoAnalysisCheckItem
				id="subheadings-keywords"
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
		const { state, count, is_primary: isPrimary } = this.props.data.result;

		return (
			<p>
				{isNaN(state)
					? __(
							"Using subheadings in your content (such as H2's or H3's) will help both the user and search engines quickly figure out what your article is about. It also helps visually section your content which in turn is great user experience. We recommend you have at least one subheading.",
							'wds'
					  )
					: state
					? sprintf(
							/* translators: %d: Subheading count */
							__(
								"You've used this keyphrase in %d subheading(s), which will help the user and search engines quickly figure out the content on your page. Good work!",
								'wds'
							),
							count
					  )
					: isPrimary
					? __(
							"Using keyphrases in any of your subheadings (such as H2's or H3's) will help both the user and search engines quickly figure out what your article is about. It's best practice to include your focus keyphrases in at least one subheading if you can.",
							'wds'
					  )
					: __(
							"You have not used this secondary keyphrase in any of your subheadings. It's best practice to include your secondary keyphrases in at least one subheading if possible.",
							'wds'
					  )}
			</p>
		);
	}

	getStatusMessage() {
		const { state, count, is_primary: isPrimary } = this.props.data.result;

		const type = isPrimary
			? __('primary', 'wds')
			: __('secondary', 'wds');

		return isNaN(state)
			? __("You don't have any subheadings", 'wds')
			: state
			? sprintf(
					/* translators: 1: primary or secondary, 2: Subheading count */
					__(
						'Your %1$s keyphrase was found in %2$d subheadings',
						'wds'
					),
					type,
					count
			  )
			: sprintf(
					/* translators: %s: primary or secondary */
					__(
						"You haven't used your %s keyphrase in any subheadings",
						'wds'
					),
					type
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
