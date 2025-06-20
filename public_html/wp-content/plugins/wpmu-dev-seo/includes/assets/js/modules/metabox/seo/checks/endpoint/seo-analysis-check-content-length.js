import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import SeoAnalysisCheckItem from '../../seo-analysis-check-item';

export default class SeoAnalysisCheckContentLength extends React.Component {
	static defaultProps = {
		data: {},
		onIgnore: () => false,
		onUnignore: () => false,
	};

	render() {
		const { data, onIgnore, onUnignore } = this.props;

		return (
			<SeoAnalysisCheckItem
				id="content-length"
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
		const { state, min, count } = this.props.data.result;

		return (
			<p>
				{!count
					? __(
							"Unless your website is a photography blog it's generally a good idea to include content for your visitors to read, and also for Google to index. Something, anything, is better than nothing.",
							'wds'
					  )
					: state
					? sprintf(
							// translators: %d Word count.
							__(
								'Your content is longer than the recommend minimum of %d words, excellent!',
								'wds'
							),
							min
					  )
					: sprintf(
							// translators: %d Word count.
							__(
								'The best practice minimum content length for the web is %1$d words so we recommend you aim for at least this amount - the more the merrier.',
								'wds'
							),
							min
					  )}
			</p>
		);
	}

	getStatusMessage() {
		const { state, count, min } = this.props.data.result;

		return state === -1
			? __(
					"Your article doesn't have any words yet, you might want to add some content",
					'wds'
			  )
			: !state
			? sprintf(
					/* translators: %s Word count */
					__(
						'The text contains %1$d words which is less than the recommended minimum of %2$d words',
						'wds'
					),
					count,
					min
			  )
			: sprintf(
					/* translators: %s Word count */
					__(
						'The text contains %1$d words which is more than the recommended minimum of %2$d words',
						'wds'
					),
					count,
					min
			  );
	}

	getMoreInfo() {
		const { min } = this.props.data.result;

		return (
			<p>
				{sprintf(
					/* translators: %d Word count */
					__(
						"Content is ultimately the bread and butter of your SEO. Without words, your pages and posts will have a hard time ranking for the keyphrases you want them to. As a base for any article best practice suggests a minimum of %d words, with 1000 being a good benchmark and 1600 being the optimal. Numerous studies have uncovered that longer content tends to perform better than shorter content, with pages having 1000 words or more performing best. Whilst optimizing your content for search engines is what we're going for here, a proven bi-product is that high quality long form articles also tend to get shared more on social platforms. With the increasing power of social media as a tool for traffic it's a nice flow on effect of writing those juicy high quality articles your readers are waiting for.",
						'wds'
					),
					min
				)}
			</p>
		);
	}
}
