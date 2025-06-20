import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import SeoAnalysisCheckItem from '../../seo-analysis-check-item';

export default class SeoAnalysisCheckKeywordDensity extends React.Component {
	static defaultProps = {
		data: {},
		onIgnore: () => false,
		onUnignore: () => false,
	};

	render() {
		const { data, onIgnore, onUnignore } = this.props;

		return (
			<SeoAnalysisCheckItem
				id="keyword-density"
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
		const { state, density, min, max, type } = this.props.data.result;

		return (
			<p>
				{0 === density
					? sprintf(
							/* translators: 1, 2: Density range */
							__(
								"Currently you haven't used any keyphrases in your content. The recommended density is %1$d-%2$d%%. A low keyphrase density means your content has less chance of ranking highly for your chosen focus keyphrases.",
								'wds'
							),
							min,
							max
					  )
					: state
					? sprintf(
							/* translators: 1, 2: Density range, 3: Current density, 4: type of keyphrase */
							__(
								'Your %4$s density is %3$s%% which is within the recommended %1$d-%2$d%%, nice work! This means your content has a better chance of ranking highly for your chosen focus keyphrases, without appearing as spam.',
								'wds'
							),
							min,
							max,
							density,
							type
					  )
					: density < min
					? sprintf(
							/* translators: 1, 2: Density range, 3: Current density, 4: type of keyphrase */
							__(
								'Currently your %4$s density is %3$s%% which is below the recommended %1$d-%2$d%%. A low keyphrase density means your content has less chance of ranking highly for your chosen focus keyphrases.',
								'wds'
							),
							min,
							max,
							density,
							type
					  )
					: sprintf(
							/* translators: 1, 2: Density range, 3: Current density, 4: type for keyphrase */
							__(
								'Currently your %4$s density is %3$s%% which is greater than the recommended %1$d-%2$d%%. If your content is littered with too many focus keyphrases, search engines can penalize your content and mark it as spam.',
								'wds'
							),
							min,
							max,
							density,
							type
					  )}
			</p>
		);
	}

	getStatusMessage() {
		const { state, density, min, max, type } = this.props.data.result;

		return 0 === density
			? __("You haven't used any keyphrases yet", 'wds')
			: state
			? sprintf(
					/* translators: 1: primary or secondary, 2: low, 3: high */
					__(
						'Your %1$s density is between %2$d%% and %3$d%%',
						'wds'
					),
					type,
					min,
					max
			  )
			: density < min
			? sprintf(
					/* translators: 1: primary or secondary, 2: low */
					__(
						'Your %1$s density is less than %2$d%%',
						'wds'
					),
					type,
					min
			  )
			: sprintf(
					/* translators: 1: primary or secondary, 2: high */
					__(
						'Your %1$s density is greater than %2$d%%',
						'wds'
					),
					type,
					max
			  );
	}

	getMoreInfo() {
		const { min, max } = this.props.data.result;

		return (
			<p>
				{sprintf(
					/* translators: 1, 2: Density range */
					__(
						"Keyword density is all about making sure your content is populated with enough keyphrases to give it a better chance of appearing higher in search results. One way of making sure people will be able to find our content is using particular focus keyphrases, and using them as much as naturally possible in our content. In doing this we are trying to match up the keywords that people are likely to use when searching for this article or page, so try to get into your visitors mind and picture them typing a search into Google. While we recommend aiming for %1$d-%2$d%% density, remember content is king and you don't want your article to end up sounding like a robot. Get creative and utilize the page title, image caption, and subheadings.",
						'wds'
					),
					min,
					max
				)}
			</p>
		);
	}
}
