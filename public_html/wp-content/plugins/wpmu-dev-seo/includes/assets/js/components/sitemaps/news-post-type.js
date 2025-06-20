import React from 'react';
import AccordionItem from '../accordion-item';
import AccordionItemOpenIndicator from '../accordion-item-open-indicator';
import Checkbox from '../checkbox';
import { __, sprintf } from '@wordpress/i18n';
import FormField from '../form-field';
import Select from '../input-fields/select';
import ajaxUrl from 'ajaxUrl';

export default class NewsPostType extends React.Component {
	static defaultProps = {
		name: '',
		label: '',
		included: false,
		excluded: [],
		taxonomies: {},
		onPostTypeInclusionChange: () => false,
		onTermIdsExclusion: () => false,
		onPostExclusion: () => false,
	};

	render() {
		const { name, taxonomies } = this.props;

		if (!this.props.included) {
			return (
				<AccordionItem
					className={`sui-builder-field wds-news-post-type-${name} disabled`}
					header={this.getHeader()}
				/>
			);
		}

		return (
			<AccordionItem
				className={`sui-builder-field wds-news-post-type-${name}`}
				header={
					<React.Fragment>
						{this.getHeader()}
						<AccordionItemOpenIndicator />
					</React.Fragment>
				}
			>
				{Object.keys(taxonomies).map((taxonomyName) => {
					const taxonomy = taxonomies[taxonomyName];

					return (
						<div
							className={`wds-news-taxonomy wds-news-taxonomy-${taxonomyName}`}
							key={taxonomyName}
						>
							<FormField
								label={sprintf(
									// translators: %s: Taxonomy label.
									__('Exclude %s', 'wds'),
									taxonomy.label
								)}
								description={sprintf(
									// translators: %s: Taxonomy label.
									__(
										'Search for and select %s that should be excluded from the Google News sitemap.',
										'wds'
									),
									taxonomy.label.toLowerCase()
								)}
								formControl={Select}
								selectedValue={taxonomy.excluded}
								multiple={true}
								ajaxUrl={this.getTermAjaxSearchUrl(
									taxonomyName
								)}
								loadTextAjaxUrl={this.getTermAjaxSearchUrl(
									taxonomyName,
									'text'
								)}
								onSelect={(values) =>
									this.props.onTermIdsExclusion(
										this.props.name,
										taxonomyName,
										values
									)
								}
							/>
						</div>
					);
				})}

				<FormField
					label={sprintf(
						// translators: %s: Taxonomy label.
						__('Exclude %s', 'wds'),
						this.props.label
					)}
					description={sprintf(
						// translators: %s: Taxonomy label.
						__(
							'Search for and select %s that should be excluded from the Google News sitemap.',
							'wds'
						),
						this.props.label.toLowerCase()
					)}
					formControl={Select}
					selectedValue={this.props.excluded}
					multiple={true}
					ajaxUrl={this.getAjaxSearchUrl()}
					loadTextAjaxUrl={this.getAjaxSearchUrl('text')}
					onSelect={(values) =>
						this.props.onPostExclusion(this.props.name, values)
					}
				/>
			</AccordionItem>
		);
	}

	getAjaxSearchUrl(requestType = '') {
		return sprintf(
			'%1$s?action=wds_search_post&type=%2$s&request_type=%3$s',
			ajaxUrl,
			this.props.name,
			requestType
		);
	}

	getTermAjaxSearchUrl(taxonomy, requestType = '') {
		return sprintf(
			'%1$s?action=wds-search-term&type=%2$s&request_type=%3$s',
			ajaxUrl,
			taxonomy,
			requestType
		);
	}

	getHeader() {
		return (
			<React.Fragment>
				<Checkbox
					checked={this.props.included}
					onChange={(checked) =>
						this.props.onPostTypeInclusionChange(
							this.props.name,
							checked
						)
					}
				/>

				<div className="sui-builder-field-label">
					<span>{this.props.label}</span>
					<span>({this.props.name})</span>
				</div>
			</React.Fragment>
		);
	}
}
