import React from 'react';
import LighthouseUtil from '../utils/lighthouse-util';
import Modal from '../../modal';

export default class LighthouseTapTargetsTable extends React.Component {
	static defaultProps = {
		id: '',
		header: [],
		rows: [],
	};

	constructor(id, header) {
		super(id, header);

		const tapTargetScreenshots = [],
			overlappingScreenshots = [];

		this.props.rows.forEach((row) => {
			tapTargetScreenshots.push(
				LighthouseUtil.getThumbnail(
					this.props.id,
					(screenshot) => this.openScreenshotZoomModal(screenshot),
					row.tapTargetNodeId,
					100,
					75
				)
			);
			overlappingScreenshots.push(
				LighthouseUtil.getThumbnail(
					this.props.id,
					(screenshot) => this.openScreenshotZoomModal(screenshot),
					row.overlappingNodeId,
					100,
					75
				)
			);
		});

		this.state = {
			openDialog: false,
			screenshot: '',
			tapTargetScreenshots,
			overlappingScreenshots,
		};
	}

	render() {
		const { rows } = this.props;

		if (!rows.length) {
			return '';
		}

		const { header } = this.props;

		const {
			openDialog,
			tapTargetScreenshots,
			overlappingScreenshots,
			screenshot,
		} = this.state;

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
						</tr>

						{rows.map((row, index) => {
							const tapTargetScreenshot =
								tapTargetScreenshots[index];
							const overlappingScreenshot =
								overlappingScreenshots[index];

							return (
								<tr key={index}>
									{row.details &&
										row.details.map((col, colIndex) => (
											<td key={colIndex}>
												<div
													style={{
														display: 'flex',
														alignItems: 'center',
													}}
												>
													<div
														style={{
															marginRight: '10px',
															wordBreak:
																'break-all',
														}}
													>
														{col}
													</div>

													{colIndex === 0 &&
														tapTargetScreenshot}
													{colIndex === 2 &&
														overlappingScreenshot}
												</div>
											</td>
										))}
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
