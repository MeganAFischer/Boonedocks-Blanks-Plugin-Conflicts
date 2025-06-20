import React from 'react';
import { __ } from '@wordpress/i18n';
import LighthouseUtil from '../utils/lighthouse-util';
import Modal from '../../modal';

export default class LighthouseTable extends React.Component {
	static defaultProps = {
		id: '',
		header: [],
		rows: [],
	};

	constructor(props) {
		super(props);

		this.state = {
			openDialog: false,
			screenshot: '',
		};
	}

	render() {
		const { rows } = this.props;

		if (!rows.length) {
			return '';
		}

		const { id, header } = this.props;

		const hasScreenshots = !!rows.find((row) => !!row.screenshot);

		const { openDialog, screenshot } = this.state;

		return (
			<React.Fragment>
				{openDialog && (
					<Modal
						id="wds-lighthouse-screenshot-zoom"
						onClose={() =>
							this.setState({ openDialog: false, screenshot: '' })
						}
					>
						{screenshot}
					</Modal>
				)}

				<table className="sui-table">
					<tbody>
						<tr>
							{header.map((headCol, index) => (
								<th key={index}>{headCol}</th>
							))}

							{hasScreenshots && (
								<th className="wds-lh-screenshot-th">
									{__('Screenshot', 'wds')}
								</th>
							)}
						</tr>

						{rows.map((row, index) => {
							const thumbnail = LighthouseUtil.getThumbnail(
								id,
								(ss) => this.openScreenshotZoomModal(ss),
								row.screenshot
							);

							const cols = row.cols
								? row.cols
								: Array.isArray(row)
								? row
								: [row];

							return (
								<tr key={index}>
									{cols.map((col, colIndex) => (
										<td key={colIndex}>{col}</td>
									))}
									{!!thumbnail && <td>{thumbnail}</td>}
								</tr>
							);
						})}
					</tbody>
				</table>
			</React.Fragment>
		);
	}

	openScreenshotZoomModal(screenshot) {
		this.setState({ openDialog: true, screenshot });
	}
}
