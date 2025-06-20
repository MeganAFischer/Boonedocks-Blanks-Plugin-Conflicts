import React from 'react';
import LighthouseCheckItem from '../lighthouse-check-item';
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import LighthouseUtil from '../utils/lighthouse-util';
import Notice from '../../notices/notice';
import LighthouseTag from '../lighthouse-tag';
import LighthouseTable from '../tables/lighthouse-table';
import ConfigValues from '../../../es6/config-values';

export default class LighthouseCheckImageAlt extends React.Component {
	static defaultProps = {
		id: 'image-alt',
	};

	render() {
		return (
			<LighthouseCheckItem
				id={this.props.id}
				successTitle={__(
					'Image elements have [alt] attributes',
					'wds'
				)}
				failureTitle={__(
					'Image elements do not have [alt] attributes',
					'wds'
				)}
				successDescription={this.successDescription()}
				failureDescription={this.failureDescription()}
				copyDescription={() => this.copyDescription()}
				actionButton={LighthouseUtil.editHomepageButton()}
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
							'Informative elements should aim for short, descriptive alternate text. Decorative elements can be ignored with an empty alt attribute.',
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
							'Way to go! It appears all your images have alt image text.',
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
						message={createInterpolateElement(
							__(
								"We've detected some of your images are missing <strong>alt tag</strong> text.",
								'wds'
							),
							{
								strong: <strong />,
							}
						)}
					/>

					{this.renderTable()}
				</div>

				<div className="wds-lh-section">
					<strong>
						{__(
							'How to add alternative text to images',
							'wds'
						)}
					</strong>
					<p>
						{__(
							'Provide an alt attribute for every <img> element. If the image fails to load, the alt text is used as a placeholder so users have a sense of what the image was trying to convey.',
							'wds'
						)}
					</p>

					<ul>
						<li style={{ margin: '25px 0' }}>
							{__(
								'Most images should have short, descriptive text:',
								'wds'
							)}
							<br />
							<div
								className="wds-lh-highlight"
								style={{ marginTop: '10px', border: 'none' }}
							>
								<LighthouseTag
									tag="img"
									attributes={{
										alt: __(
											'Audits set-up in Chrome DevTools ',
											'wds'
										),
										src: '...',
									}}
								/>
							</div>
						</li>

						<li style={{ marginBottom: '25px' }}>
							{__(
								'If the image acts as decoration and does not provide any useful content, give it an empty alt="" attribute to remove it from the accessibility tree:',
								'wds'
							)}
							<br />
							<div
								className="wds-lh-highlight"
								style={{ marginTop: '10px', border: 'none' }}
							>
								<LighthouseTag
									tag="img"
									attributes={{
										alt: '',
										src: 'background.png',
									}}
								/>
							</div>
						</li>
					</ul>

					<p>
						{createInterpolateElement(
							__(
								'See also <a>Include text alternatives for images and objects</a>.',
								'wds'
							),
							{
								a: (
									<a
										href="https://web.dev/labels-and-text-alternatives/#include-text-alternatives-for-images-and-objects"
										target="_blank"
										rel="noreferrer"
									/>
								),
							}
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
				header={[__('Failing Elements', 'wds'), __('Selector', 'wds')]}
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
			__('Audit Type: Content audits', 'wds') +
			'\n\n' +
			__(
				'Failing Audit: Image elements do not have [alt] attributes',
				'wds'
			) +
			'\n\n' +
			__(
				"Status: We've detected some of your images are missing alt tag text.",
				'wds'
			) +
			'\n\n' +
			this.getFlattenedDetails(
				[__('Failing Elements', 'wds'), __('Selector', 'wds')],
				this.getRows()
			) +
			__('Overview:', 'wds') +
			'\n' +
			__(
				'Informative elements should aim for short, descriptive alternate text. Decorative elements can be ignored with an empty alt attribute.',
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
					cols: [item.node?.snippet, item.node?.selector],
					screenshot: item.node?.lhId,
				});
			});
		}

		return rows;
	}

	getFlattenedDetails(header, rows) {
		if (!rows.length) {
			return [];
		}

		const flattenedDetails = [];

		rows.forEach((row) => {
			row.cols.forEach((col, index) => {
				let colHeader = header[index];

				if (colHeader) {
					colHeader =
						colHeader === 'object' ? colHeader : colHeader + ': ';
				}
				flattenedDetails.push(colHeader + col);
			});
		});

		return flattenedDetails.join('\n') + '\n\n';
	}
}
