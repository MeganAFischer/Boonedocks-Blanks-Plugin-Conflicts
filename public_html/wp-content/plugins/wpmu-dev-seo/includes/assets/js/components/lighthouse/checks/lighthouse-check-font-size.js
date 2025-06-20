import React from 'react';
import Notice from '../../notices/notice';
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import LighthouseUtil from '../utils/lighthouse-util';
import LighthouseCheckItem from '../lighthouse-check-item';
import LighthouseTable from '../tables/lighthouse-table';
import ConfigValues from '../../../es6/config-values';

export default class LighthouseCheckFontSize extends React.Component {
	static defaultProps = {
		id: 'font-size',
	};

	render() {
		return (
			<LighthouseCheckItem
				id={this.props.id}
				successTitle={__(
					'Document uses legible font sizes',
					'wds'
				)}
				failureTitle={__(
					"Document doesn't use legible font sizes",
					'wds'
				)}
				successDescription={this.successDescription()}
				failureDescription={this.failureDescription()}
				copyDescription={() => this.copyDescription()}
			/>
		);
	}

	commonDescription() {
		return (
			<React.Fragment>
				<div className="wds-lh-section">
					<strong>{__('Overview', 'wds')}</strong>
					<p>
						{__(
							'Many search engines rank pages based on how mobile-friendly they are. Font sizes smaller than 12px are often difficult to read on mobile devices and may require users to zoom in to display text at a comfortable reading size.',
							'wds'
						)}
					</p>
				</div>
			</React.Fragment>
		);
	}

	successDescription() {
		return (
			<React.Fragment>
				{this.commonDescription()}
				<div className="wds-lh-section">
					<strong>{__('Status', 'wds')}</strong>
					<Notice
						type="success"
						icon="sui-icon-info"
						message={__(
							'Document uses legible font sizes, nice work!',
							'wds'
						)}
					/>
				</div>
			</React.Fragment>
		);
	}

	failureDescription() {
		return (
			<React.Fragment>
				{this.commonDescription()}
				<div className="wds-lh-section">
					<strong>{__('Status', 'wds')}</strong>
					<Notice
						type="warning"
						icon="sui-icon-info"
						message={__(
							"Document doesn't use legible font sizes.",
							'wds'
						)}
					/>
				</div>

				<div className="wds-lh-section wds-lh-font-sizes-table">
					<p>
						{__(
							'Lighthouse flags pages on which 60% or more of the text has a font size smaller than 12px.',
							'wds'
						)}
					</p>
					{this.renderTable()}
				</div>

				<div className="wds-lh-section">
					<strong>{__('How to fix illegible fonts', 'wds')}</strong>
					<p>
						{__(
							'If Lighthouse reports Text is illegible because of a missing viewport config, add a <meta name="viewport" content="width=device-width, initial-scale=1"> tag to the <head> of your document.',
							'wds'
						)}
					</p>
				</div>
			</React.Fragment>
		);
	}

	renderTable() {
		return (
			<LighthouseTable
				id={this.props.id}
				header={[
					__('Selector', 'wds'),
					__('Font Size', 'wds'),
					__('% of Page Text', 'wds'),
				]}
				rows={this.getRows()}
			/>
		);
	}

	copyDescription() {
		return (
			sprintf(
				// translators: %s: Device label.
				__('Tested Device: %s', 'wds'),
				LighthouseUtil.getDeviceLabel()
			) +
			'\n' +
			__('Audit Type: Responsive audits', 'wds') +
			'\n\n' +
			__(
				"Failing Audit: Document doesn't use legible font sizes",
				'wds'
			) +
			'\n\n' +
			__(
				"Status: Document doesn't use legible font sizes.",
				'wds'
			) +
			'\n' +
			__(
				'Lighthouse flags pages on which 60% or more of the text has a font size smaller than 12px.',
				'wds'
			) +
			'\n\n' +
			__('Overview:', 'wds') +
			'\n' +
			__(
				'Many search engines rank pages based on how mobile-friendly they are. Font sizes smaller than 12px are often difficult to read on mobile devices and may require users to zoom in to display text at a comfortable reading size.',
				'wds'
			) +
			'\n\n' +
			createInterpolateElement(
				sprintf(
					// translators: %s: plugin title
					__(
						'For more information please check the SEO Audits section in <strong>%s</strong> plugin.',
						'wds'
					),
					ConfigValues.get('plugin_title', 'admin')
				),
				{ strong: <strong /> }
			)
		);
	}

	getRows() {
		const items = LighthouseUtil.getRawDetails(this.props.id)?.items;

		const rows = [];

		if (items) {
			items.forEach((item) => {
				rows.push([
					item.selector?.snippet,
					item.fontSize,
					item.coverage,
				]);
			});
		}

		return rows;
	}
}
