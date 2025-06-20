import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import SeoAnalysisCheckItem from '../../seo-analysis-check-item';
import ConfigValues from '../../../../../es6/config-values';

const minLength = ConfigValues.get('title_min_length', 'metabox');
const maxLength = ConfigValues.get('title_max_length', 'metabox');

export default class SeoAnalysisCheckTitleLength extends React.Component {
	static defaultProps = {
		data: {},
		onIgnore: () => false,
		onUnignore: () => false,
	};

	render() {
		const { data, onIgnore, onUnignore } = this.props;

		return (
			<SeoAnalysisCheckItem
				id="title-length"
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
		const length = parseInt(this.props.data.result.length);

		return (
			<p>
				{length >= minLength && length <= maxLength
					? sprintf(
							/* translators: 1: Current length, 2,3: min/max length */
							__(
								'Your SEO title is %1$d characters which is between the recommended best practice of %2$d-%3$d characters.',
								'wds'
							),
							length,
							minLength,
							maxLength
					  )
					: length > maxLength
					? sprintf(
							/* translators: 1: Current length, 2,3: min/max length */
							__(
								'Your SEO title is %1$d characters which is greater than the recommended %3$d characters. Best practice is between %2$d and %3$d characters, with 60 being the sweet spot.',
								'wds'
							),
							length,
							minLength,
							maxLength
					  )
					: length < minLength
					? sprintf(
							/* translators: 1: Current length, 2,3: min/max length */
							__(
								'Your SEO title is %1$d characters which is less than the recommended %2$d characters. Best practice is between %2$d and %3$d characters, with 60 being the sweet spot.',
								'wds'
							),
							length,
							minLength,
							maxLength
					  )
					: !length
					? sprintf(
							/* translators: 1: Current length, 2,3: min/max length */
							__(
								'You have NOT written an SEO specific title for this article. We recommend an SEO specific title between %2$d and %3$d characters, optimized with your focus keyphrases.',
								'wds'
							),
							length,
							minLength,
							maxLength
					  )
					: ''}
			</p>
		);
	}

	getStatusMessage() {
		const { state } = this.props.data.result;
		return typeof state === 'boolean'
			? __('Your SEO title is a good length', 'wds')
			: 0 === state
			? __("You haven't added an SEO title yet", 'wds')
			: state > 0
			? __('Your SEO title is too long', 'wds')
			: __('Your SEO title is too short', 'wds');
	}

	getMoreInfo() {
		const { length } = this.props.data.result;

		return (
			<p>
				{sprintf(
					/* translators: 1: Current characters length, 2,3: Recommend length range */
					__(
						"Your SEO title is the most important element because it is what users will see in search engine results. You'll want to make sure that you have your focus keyphrases in there, that it's a nice length, and that people will want to click on it. Best practices suggest keeping your titles between %2$d and %3$d characters including spaces, though in some cases 60 is the sweetspot. The length is important both for SEO ranking but also how your title will show up in search engines - long titles will be cut off visually and look bad. Unfortunately there isn't a rule book for SEO titles, just remember to make your title great for SEO but also (most importantly) readable and enticing for potential visitors to click on.",
						'wds'
					),
					length,
					minLength,
					maxLength
				)}
			</p>
		);
	}
}
