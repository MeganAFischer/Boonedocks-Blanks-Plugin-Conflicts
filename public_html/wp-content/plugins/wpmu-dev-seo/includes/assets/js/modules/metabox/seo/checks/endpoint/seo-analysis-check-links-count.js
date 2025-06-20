import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import SeoAnalysisCheckItem from '../../seo-analysis-check-item';
import { createInterpolateElement } from '@wordpress/element';

export default class SeoAnalysisCheckLinksCount extends React.Component {
	static defaultProps = {
		data: {},
		onIgnore: () => false,
		onUnignore: () => false,
	};

	render() {
		const { data, onIgnore, onUnignore } = this.props;

		return (
			<SeoAnalysisCheckItem
				id="links-count"
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
		const { state, total, internal } = this.props.data.result;

		return (
			<p>
				{state
					? sprintf(
							/* translators: 3: Number of internal links. */
							__(
								"Internal links help search engines crawl your website, effectively pointing them to more pages to index on your website. You've already added %1$d links, nice work!",
								'wds'
							),
							internal
					  )
					: !total
					? __(
							'Internal links help search engines crawl your website, effectively pointing them to more pages to index on your website. You should consider adding at least one internal link to another related article.',
							'wds'
					  )
					: !internal
					? __(
							'Internal links help search engines crawl your website, effectively pointing them to more pages to index on your website. You should consider adding at least one internal link to another related article.',
							'wds'
					  )
					: ''}
			</p>
		);
	}

	getStatusMessage() {
		const { state, total, internal } = this.props.data.result;

		const external = total - internal;

		return state
			? sprintf(
					/* translators: 1, 2: Number of internal/external links. */
					__(
						'You have %1$d internal and %2$d external links in your content',
						'wds'
					),
					internal,
					external
			  )
			: !total
			? __(
					"You haven't added any internal or external links in your content",
					'wds'
			  )
			: !internal
			? sprintf(
					/* translators: %d: Number of external links. */
					__(
						'You have 0 internal and %d external links in your content',
						'wds'
					),
					external
			  )
			: '';
	}

	getMoreInfo() {
		return (
			<p>
				{__(
					"Internal links are important for linking together related content. Search engines will 'crawl' through your website, indexing pages and posts as they go. To help them discover all the juicy content your website has to offer, it's wise to make sure your content has internal links built in for bots to follow and index.",
					'wds'
				)}
				<br />
				<br />
				{createInterpolateElement(
					__(
						"External links don't benefit your SEO by having them in your own content, but you'll want to try and get as many other websites linking to your articles and pages as possible. Search engines treat links to your website as a 'third party vote' in favour of your website - like a vote of confidence. Since they're the hardest form of 'validation' to get (another website has to endorse you!) search engines weight them heavily when considering page rank. For more info: <a>https://moz.com/learn/seo/internal-link</a>",
						'wds'
					),
					{
						a: (
							<a
								href="https://moz.com/learn/seo/internal-link"
								target="_blank"
								rel="noreferrer"
							/>
						),
					}
				)}
				<br />
				<br />
				{__(
					"Note: This check is only looking at the content your page is outputting and doesn't include your main navigation. Blogs with lots of posts will benefit the most from this method, as it aids Google in finding and indexing all of your content, not just the latest articles.",
					'wds'
				)}
			</p>
		);
	}
}
