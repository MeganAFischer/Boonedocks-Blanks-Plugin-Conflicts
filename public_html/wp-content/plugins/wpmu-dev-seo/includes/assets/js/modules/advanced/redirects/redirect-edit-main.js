import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { isNonEmpty } from '../../../utils/validators';
import SelectField from '../../../components/form-fields/select-field';
import {
	getRedirectTypes,
	isNonRedirectType,
	SourceFieldNonRegex,
	SourceFieldRegex,
} from '../../../utils/redirect-utils';
import Notice from '../../../components/notices/notice';
import ConfigValues from '../../../es6/config-values';
import { connect } from 'react-redux';
import RedirectRulesGeo from './redirect-rules-geo';
import UrlInputField from '../../../components/form-fields/url-input-field';
import Toggle from '../../../components/toggle';

const homeUrl = ConfigValues.get('home_url', 'admin') || '';

class RedirectEditMain extends React.Component {
	static defaultProps = {
		bulkUpdating: false,
	};

	renderSource() {
		const { source, options, loading, updateFrom } = this.props;

		const isRegex = options.includes('regex');
		const maybeIsRegex = /[\[*^$\\{|]/g.test(source);

		const SourceField = isRegex ? SourceFieldRegex : SourceFieldNonRegex;

		return (
			<>
				<SourceField
					id="wds-source-field"
					label={__('Redirect From', 'wds')}
					description={
						isRegex
							? __(
									'Enter regex to match absolute URLs.',
									'wds'
							  )
							: ''
					}
					value={source}
					placeholder={
						isRegex
							? sprintf(
									// translators: %s: Home url.
									__('E.g. %s/(.*)-cats', 'wds'),
									homeUrl
							  )
							: __('E.g. /cats', 'wds')
					}
					onChange={(src, valid) => updateFrom(src, valid)}
					disabled={loading}
					validateOnInit={isNonEmpty(source)}
				/>
				{maybeIsRegex && !isRegex && (
					<Notice
						type="info"
						message={createInterpolateElement(
							__(
								'To configure a regex redirect, you must first select <strong>Regex</strong> in the Advanced settings below.',
								'wds'
							),
							{
								strong: <strong />,
							}
						)}
					/>
				)}
			</>
		);
	}

	renderDestination() {
		const { bulkUpdating, bulkType, type, rulesEnabled, dstDisabled } =
			this.props;

		if (
			(bulkUpdating && bulkType && isNonRedirectType(type)) ||
			(!bulkUpdating && isNonRedirectType(type)) ||
			(rulesEnabled && dstDisabled)
		) {
			return '';
		}

		const { destination, bulkTo, loading, updateTo, toggleBulkTo } =
			this.props;

		return (
			<>
				{bulkUpdating && (
					<div className="sui-form-field wds-toggle-field">
						<Toggle
							label={__('Redirect URL', 'wds')}
							checked={bulkTo}
							onChange={() => toggleBulkTo()}
						/>
					</div>
				)}

				{(!bulkUpdating || bulkTo) && (
					<UrlInputField
						label={__('Redirect To', 'wds')}
						placeholder={__(
							'Enter url, page or post title',
							'wds'
						)}
						value={destination}
						onSelect={(val) => updateTo(val)}
						disabled={loading}
					/>
				)}
			</>
		);
	}

	renderType() {
		const {
			type,
			loading,
			updateType,
			bulkUpdating,
			bulkType,
			toggleBulkType,
			defaultType,
		} = this.props;

		return (
			<>
				{bulkUpdating && (
					<div className="sui-form-field wds-toggle-field">
						<Toggle
							label={__('Redirect Type', 'wds')}
							checked={bulkType}
							onChange={() => toggleBulkType()}
						/>
					</div>
				)}

				{(!bulkUpdating || bulkType) && (
					<SelectField
						label={__('Redirect Type', 'wds')}
						options={getRedirectTypes()}
						selectedValue={type || defaultType}
						onSelect={(selectedType) => updateType(selectedType)}
						disabled={loading}
					/>
				)}
			</>
		);
	}

	render() {
		const { type, bulkUpdating, bulkTo, bulkType, rulesEnabled } =
			this.props;

		return (
			<>
				{bulkUpdating && !bulkTo && !bulkType && !rulesEnabled && (
					<Notice
						type={false}
						message={__(
							'Please enable the values you want to bulk update.',
							'wds'
						)}
					/>
				)}
				{!bulkUpdating && this.renderSource()}

				{this.renderDestination()}

				{this.renderType()}

				{((bulkUpdating && (!bulkType || !isNonRedirectType(type))) ||
					(!bulkUpdating && !isNonRedirectType(type))) && (
					<RedirectRulesGeo />
				)}
			</>
		);
	}
}

const mapStateToProps = (state) => ({ ...state });

const mapDispatchToProps = {
	updateFrom: (source, valid) => ({
		type: 'UPDATE_FROM',
		payload: {
			source,
			valid,
		},
	}),
	updateTo: (destination) => ({
		type: 'UPDATE_TO',
		payload: destination,
	}),
	updateType: (type) => ({
		type: 'UPDATE_TYPE',
		payload: { type },
	}),
	toggleBulkTo: () => ({
		type: 'TOGGLE_BULK_TO',
	}),
	toggleBulkType: () => ({
		type: 'TOGGLE_BULK_TYPE',
	}),
};

export default connect(mapStateToProps, mapDispatchToProps)(RedirectEditMain);
