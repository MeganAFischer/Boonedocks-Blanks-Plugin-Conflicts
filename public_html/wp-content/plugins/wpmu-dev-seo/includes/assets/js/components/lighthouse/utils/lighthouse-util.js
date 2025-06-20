import UrlUtil from '../../../utils/url-util';
import { __ } from '@wordpress/i18n';
import React from 'react';
import Button from '../../button';
import ConfigValues from '../../../es6/config-values';

export default class LighthouseUtil {
	static getDeviceLabel() {
		const device = !!UrlUtil.getQueryParam('device')
			? UrlUtil.getQueryParam('device')
			: 'desktop';

		return device === 'desktop' ? 'Desktop' : 'Mobile';
	}

	static editHomepageButton() {
		const homeUrl = ConfigValues.get('homepage_url', 'lighthouse');
		if (!homeUrl || homeUrl === '') {
			return '';
		}
		return (
			<Button
				href={ConfigValues.get('homepage_url', 'lighthouse')}
				icon="sui-icon-pencil"
				text={__('Edit Homepage', 'wds')}
			/>
		);
	}

	static testingTool() {
		return ConfigValues.get('testing_tool', 'lighthouse');
	}

	static getReport() {
		return ConfigValues.get('report', 'lighthouse');
	}

	static getRawDetails(id) {
		const report = this.getReport();

		return report[id]?.details;
	}

	static getFlattenedDetails(header, rows) {
		if (!rows.length) {
			return [];
		}

		const flattenedDetails = [];

		rows.forEach((row) => {
			row.forEach((col, index) => {
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

	static getThumbnail(
		id,
		handler,
		nodeId,
		thumbWidth = 160,
		thumbHeight = 120
	) {
		const screenshot = this.getScreenshotMarkup(id, nodeId, 600, 450);
		const thumbnail = this.getScreenshotMarkup(
			id,
			nodeId,
			thumbWidth,
			thumbHeight,
			handler,
			screenshot
		);

		if (!thumbnail) {
			return '';
		}

		return (
			<div className="wds-lighthouse-thumbnail-container">
				{thumbnail}
			</div>
		);
	}

	static getScreenshotMarkup(
		id,
		nodeId,
		scaledFrameWidth,
		scaledFrameHeight,
		handler,
		screenshot
	) {
		if (!nodeId) {
			return '';
		}

		const report = this.getReport();

		const details = report['full-page-screenshot']?.details;

		const data = details?.screenshot?.data;
		const width = details?.screenshot?.width;
		const height = details?.screenshot?.height;

		if (!data || !width || !height) {
			return;
		}

		const node = details?.nodes[nodeId];
		const nodeDetails = [
			'top',
			'right',
			'bottom',
			'left',
			'width',
			'height',
		];
		nodeDetails.forEach((nodeDetail) => {
			if (!node[nodeDetail]) {
				return '';
			}
		});

		if (!node.width || !node.height) {
			return '';
		}

		const scale = scaledFrameWidth / width;

		const scaledHeight = height * scale;
		if (scaledHeight < scaledFrameHeight) {
			scaledFrameHeight = scaledHeight;
		}

		const frameHeight = (scaledFrameHeight / scaledHeight) * height;
		const topOffset = this.calculateTopOffset(node, frameHeight, height);

		return (
			<React.Fragment>
				<div
					className="wds-lighthouse-screenshot"
					style={{
						'--element-screenshot-url': `url(${data})`,
						'--element-screenshot-width': `${width}px`,
						'--element-screenshot-height': `${height}px`,
						'--element-screenshot-scaled-height': `${scaledFrameHeight}px`,
						'--element-screenshot-scaled-width': `${scaledFrameWidth}px`,
						'--element-screenshot-scale': `${scale}`,
						'--element-screenshot-top-offset': `-${topOffset}px`,
						'--element-screenshot-highlight-width': `${node.width}px`,
						'--element-screenshot-highlight-height': `${node.height}px`,
						'--element-screenshot-highlight-top': `${node.top}px`,
						'--element-screenshot-highlight-left': `${node.left}px`,
						'--element-screenshot-highlight-left-width': `${
							node.left + node.width
						}px`,
						'--element-screenshot-highlight-top-height': `${
							node.top + node.height
						}px`,
					}}
					onClick={(e) => {
						if (handler && screenshot) {
							e.preventDefault();
							handler(screenshot);
						}
					}}
				>
					<div className="wds-lighthouse-screenshot-inner">
						<div className="wds-lighthouse-screenshot-frame">
							<div className="wds-lighthouse-screenshot-image" />
							<div className="wds-lighthouse-screenshot-marker" />
							<div className="wds-lighthouse-screenshot-clip" />
						</div>
					</div>
				</div>
			</React.Fragment>
		);
	}

	static calculateTopOffset(node, frameHeight, screenshotHeight) {
		if (node.height > frameHeight) {
			return node.top;
		}

		if (node.bottom < frameHeight) {
			return 0;
		}

		const idealSpace = (frameHeight - node.height) / 2;
		const spaceAvailableUnder = screenshotHeight - node.bottom;
		if (spaceAvailableUnder < idealSpace) {
			return screenshotHeight - frameHeight;
		}

		const spaceAvailableOver =
			screenshotHeight - spaceAvailableUnder - node.height;
		if (spaceAvailableOver < idealSpace) {
			return 0;
		}

		return node.top - idealSpace;
	}

	static isBlogPublic() {
		return !!ConfigValues.get('is_blog_public', 'lighthouse');
	}

	static isHomeNoindex() {
		return !!ConfigValues.get('is_home_no_index', 'lighthouse');
	}

	static isTabAllowed(tab) {
		return ConfigValues.get(`is_tab_${tab}_allowed`, 'lighthouse');
	}

	static isMultisite() {
		return ConfigValues.get('is_multisite', 'lighthouse');
	}

	static tabUrl(tab) {
		return ConfigValues.get(`tab_${tab}_url`, 'lighthouse');
	}

	static pluginInstallUrl() {
		return ConfigValues.get('plugin_install_url', 'lighthouse');
	}

	static adminUrl(path) {
		return ConfigValues.get('admin_url', 'lighthouse') + (path ? path : '');
	}
}
