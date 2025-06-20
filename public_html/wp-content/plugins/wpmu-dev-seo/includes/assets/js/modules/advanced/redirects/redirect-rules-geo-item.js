import React from 'react';
import AccordionItem from '../../../components/accordion-item';
import { __ } from '@wordpress/i18n';
import Button from '../../../components/button';
import AccordionItemOpenIndicator from '../../../components/accordion-item-open-indicator';
import SelectField from '../../../components/form-fields/select-field';
import GeoUtil from '../../../utils/geo-util';
import { connect } from 'react-redux';
import SideTabsField from '../../../components/side-tabs-field';
import UrlInputField from '../../../components/form-fields/url-input-field';

const countryList = GeoUtil.getCountries();

class RedirectRulesGeoItem extends React.Component {
	static defaultProps = {
		conflicting: false,
	};

	askDeletingRule(e) {
		e.preventDefault();
		e.stopPropagation();

		const { index, deletingRule } = this.props;

		deletingRule(index);
	}

	render() {
		const { rules, index, conflicting, loading, updateRule } = this.props;
		const rule = rules[index];

		return (
			<AccordionItem
				className={conflicting ? 'sui-accordion-item--error' : ''}
				header={
					<>
						<div className="sui-accordion-item-title">
							{this.renderTitle()}
						</div>

						<div className="sui-accordion-col-auto">
							<Button
								icon="sui-icon-trash"
								color="red"
								onClick={(e) => this.askDeletingRule(e)}
							></Button>
							<AccordionItemOpenIndicator />
						</div>
					</>
				}
			>
				<SideTabsField
					label={__('Rule', 'wds')}
					tabs={{
						0: __('From', 'wds'),
						1: __('Not From', 'wds'),
					}}
					value={rule.indicate}
					onChange={(selectedTab) => {
						rule.indicate = selectedTab;
						updateRule(rule, index);
					}}
				/>

				<SelectField
					label={__('Countries', 'wds')}
					selectedValue={rule.countries}
					multiple={true}
					onSelect={(values) => {
						rule.countries = values;
						updateRule(rule, index);
					}}
					options={countryList}
					disabledOptions={rules.reduce((accumulator, current) => {
						return [...accumulator, ...current.countries];
					}, [])}
					disabled={loading}
					prefix={
						<span
							className="sui-icon-web-globe-world"
							aria-hidden="true"
						/>
					}
				/>

				<UrlInputField
					label={__('Redirect To', 'wds')}
					value={rule.url}
					onSelect={(updatedUrl) => {
						rule.url = updatedUrl;
						updateRule(rule, index);
					}}
					disabled={loading}
				/>
			</AccordionItem>
		);
	}

	renderTitle() {
		const { rules, index } = this.props;
		const { indicate, countries } = rules[index];

		let title = !parseInt(indicate)
			? __('From', 'wds')
			: __('Not From', 'wds');

		if (Array.isArray(countries) && countries.length) {
			title += ' ' + countryList[countries[0]];

			const restCnt = countries.length - 1;

			if (restCnt) {
				title += ' +' + restCnt + ' more';
			}
		} else {
			title += __(' No Country', 'wds');
		}

		return title;
	}
}

const mapStateToProps = (state) => ({ ...state });

const mapDispatchToProps = {
	updateRule: (rule, index) => ({
		type: 'UPDATE_RULE',
		payload: { rule, index },
	}),
	deletingRule: (index) => ({
		type: 'DELETING_RULE',
		payload: index,
	}),
};

export default connect(
	mapStateToProps,
	mapDispatchToProps
)(RedirectRulesGeoItem);
