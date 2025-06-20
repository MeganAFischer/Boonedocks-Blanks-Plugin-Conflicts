import React from 'react';
import Notice from '../../notices/notice';
import { createInterpolateElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import LighthouseUtil from '../utils/lighthouse-util';
import LighthouseCheckItem from '../lighthouse-check-item';
import LighthouseTapTargetsTable from '../tables/lighthouse-tap-targets-table';
import ConfigValues from '../../../es6/config-values';

export default class LighthouseCheckTapTargets extends React.Component {
	static defaultProps = {
		id: 'tap-targets',
	};

	render() {
		return (
			<LighthouseCheckItem
				id={this.props.id}
				successTitle={__(
					'Tap targets are sized appropriately',
					'wds'
				)}
				failureTitle={__(
					'Tap targets are not sized appropriately',
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
						{createInterpolateElement(
							__(
								'Interactive elements like buttons and links should be large enough (<strong>48x48px</strong>), and have enough space around them (<strong>8px</strong>), to be easy enough to tap without overlapping onto other elements.',
								'wds'
							),
							{ strong: <strong /> }
						)}
					</p>
					<p>
						{__(
							'Many search engines rank pages based on how mobile-friendly they are. Making sure tap targets are big enough and far enough apart from each other makes your page more mobile-friendly and accessible.',
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
							'Tap targets are sized appropriately.',
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
							'Tap targets are not sized appropriately.',
							'wds'
						)}
					/>
				</div>

				<div className="wds-lh-section">
					<p>
						{__(
							'Targets that are smaller than 48 px by 48 px or closer than 8 px apart fail the audit.',
							'wds'
						)}
					</p>
					{this.renderTable()}
				</div>

				<div className="wds-lh-section">
					<strong>{__('How to fix your tap targets', 'wds')}</strong>
					<ul>
						<li>
							{createInterpolateElement(
								__(
									"<strong>Step 1</strong>: Increase the size of tap targets that are too small. Tap targets that are <strong>48 px by 48 px</strong> never fail the audit. If you have elements that shouldn't appear any bigger (for example, icons), try increasing the padding property.",
									'wds'
								),
								{ strong: <strong /> }
							)}
						</li>
						<li>
							{createInterpolateElement(
								__(
									'<strong>Step 2</strong>: Increase the spacing between tap targets that are too close together using properties like margin. There should be at least <strong>8px</strong> between tap targets.',
									'wds'
								),
								{ strong: <strong /> }
							)}
						</li>
					</ul>
				</div>
			</React.Fragment>
		);
	}

	renderTable() {
		return (
			<LighthouseTapTargetsTable
				id={this.props.id}
				header={[
					__('Tap Target', 'wds'),
					__('Size', 'wds'),
					__('Overlapping Target', 'wds'),
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
				'Failing Audit: Tap targets are not sized appropriately',
				'wds'
			) +
			'\n\n' +
			__(
				'Status: Tap targets are not sized appropriately.',
				'wds'
			) +
			'\n' +
			__(
				'Targets that are smaller than 48 px by 48 px or closer than 8 px apart fail the audit.',
				'wds'
			) +
			'\n\n' +
			__('Overview:', 'wds') +
			'\n' +
			__(
				'Interactive elements like buttons and links should be large enough (48x48px), and have enough space around them (8px), to be easy enough to tap without overlapping onto other elements.',
				'wds'
			) +
			'\n' +
			__(
				'Many search engines rank pages based on how mobile-friendly they are. Making sure tap targets are big enough and far enough apart from each other makes your page more mobile-friendly and accessible.',
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
				rows.push({
					details: [
						item.tapTarget?.snippet,
						item.size,
						item.overlappingTargetsnippet,
					],
					tapTargetNodeId: item.tapTarget?.lhId,
					overlappingNodeId: item.overlappingTarget?.lhId,
				});
			});
		}

		return rows;
	}
}
