import React from 'react';
import { __ } from '@wordpress/i18n';
import SeoAnalysisCheckItem from '../../seo-analysis-check-item';
import Button from '../../../../../components/button';

export default class SeoAnalysisCheckKeywordsUsed extends React.Component {
	static defaultProps = {
		data: {},
		onIgnore: () => false,
		onUnignore: () => false,
	};

	render() {
		const { data, onIgnore, onUnignore } = this.props;

		return (
			<SeoAnalysisCheckItem
				id="keywords-used"
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
		const { state, used_in: usedIn } = this.props.data.result;

		return (
			<React.Fragment>
				<p>
					{!state
						? __(
								'Your primary focus keyphrase is used on the following pages:',
								'wds'
						  )
						: __(
								'Your primary focused keyphrase isn’t used on other pages on your site. Excellent!',
								'wds'
						  )}
				</p>

				{Array.isArray(usedIn) && !!usedIn.length && (
					<table className="sui-table wds-keywords-used-table">
						<thead>
							<tr>
								<th colSpan="2">
									{__(
										'Posts and Pages with the same primary focus keyphrase',
										'wds'
									)}
									<span className="sui-description">
										{__(
											'Please note that the list below displays a maximum of 10 posts and pages. There might be other posts and pages using the same keyphrase.',
											'wds'
										)}
									</span>
								</th>
							</tr>
						</thead>
						<tbody>
							{usedIn.map((post, index) => (
								<tr key={index}>
									<td>
										<div className="wds-keywords-used-post-title">
											<strong>{post.title}</strong>
											<span className="sui-tag">
												{post.type}
											</span>
										</div>
										<a
											className="wds-keywords-used-post-link"
											href={post.permalink}
										>
											<span
												className="sui-icon-link"
												aria-hidden="true"
											></span>{' '}
											{post.permalink}
										</a>
									</td>
									<td>
										<Button
											color="ghost"
											href={post.edit_link}
											target="_blank"
											rel="noreferrer"
											icon="sui-icon-pencil"
											text={__('Edit', 'wds')}
										></Button>
									</td>
								</tr>
							))}
						</tbody>
					</table>
				)}
			</React.Fragment>
		);
	}

	getStatusMessage() {
		const { state } = this.props.data.result;

		return !state
			? __(
					'Primary focus keyphrase is already used on another post/page',
					'wds'
			  )
			: __(
					'Primary focus keyphrase isn’t used on another post/page',
					'wds'
			  );
	}

	getMoreInfo() {
		return (
			<p>
				{__(
					"Using the same focus keyphrases on multiple pages or posts can affect your page's SEO ranking. Therefore, it's recommended to only use one primary focus keyphrase per page/post on your site to improve its SEO ranking.",
					'wds'
				)}
			</p>
		);
	}
}
