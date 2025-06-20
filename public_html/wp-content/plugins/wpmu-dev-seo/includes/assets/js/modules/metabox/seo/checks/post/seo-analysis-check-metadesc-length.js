import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import SeoAnalysisCheckItem from '../../seo-analysis-check-item';
import ConfigValues from '../../../../../es6/config-values';

const minLength = ConfigValues.get('metadesc_min_length', 'metabox');
const maxLength = ConfigValues.get('metadesc_max_length', 'metabox');
export default class SeoAnalysisCheckMetadescLength extends React.Component {
	static defaultProps = {
		data: {},
		onIgnore: () => false,
		onUnignore: () => false,
	};

	render() {
		const { data, onIgnore, onUnignore } = this.props;

		return (
			<SeoAnalysisCheckItem
				id="metadesc-length"
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
				{typeof state === 'boolean'
					? __(
							'Your SEO description is a good length. Having an SEO description that is either too long or too short can harm your chances of ranking highly for this article.',
							'wds'
					  )
					: 0 === state
					? __(
							"Because you haven't specified a meta description (or excerpt), search engines will automatically generate one using your content. While this is OK, you should create your own meta description making sure it contains your focus keyphrases.",
							'wds'
					  )
					: state > 0
					? __(
							"Your SEO description (or excerpt) is currently too long. Search engines generally don't like long descriptions and after a certain length the value of extra keyphrases drops significantly.",
							'wds'
					  )
					: __(
							'Your SEO description (or excerpt) is currently too short which means it has less of a chance ranking for your chosen focus keyphrases.',
							'wds'
					  )}
			</p>
		);
	}

	getStatusMessage() {
		const { state } = this.props.data.result;
		return typeof state === 'boolean'
			? __('Your meta description is a good length', 'wds')
			: 0 === state
			? __(
					"You haven't specified a meta description yet",
					'wds'
			  )
			: state > 0
			? sprintf(
					/* translators: %d into max length */
					__(
						'Your meta description is greater than %d characters',
						'wds'
					),
					maxLength
			  )
			: sprintf(
					/* translators: %d into min length */
					__(
						'Your meta description is less than %d characters',
						'wds'
					),
					minLength
			  );
	}

	getMoreInfo() {
		return (
			<p>
				{sprintf(
					/* translators: 1,2: Recommended range of characters */
					__(
						"We recommend keeping your meta descriptions between %1$d and %2$d characters (including spaces). Doing so achieves a nice balance between populating your description with keyphrases to rank highly in search engines, and also keeping it to a readable length that won't be cut off in search engine results. Unfortunately there isn't a rule book for SEO meta descriptions, just remember to make your description great for SEO, but also (most importantly) readable and enticing for potential visitors to click on.",
						'wds'
					),
					minLength,
					maxLength
				)}
			</p>
		);
	}
}
