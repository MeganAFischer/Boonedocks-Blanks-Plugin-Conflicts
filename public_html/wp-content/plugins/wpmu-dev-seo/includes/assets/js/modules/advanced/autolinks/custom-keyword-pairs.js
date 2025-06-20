import React from 'react';
import Button from '../../../components/button';
import Dropdown from '../../../components/dropdown';
import DropdownButton from '../../../components/dropdown-button';
import Pagination from '../../../components/navigations/pagination';
import { __ } from '@wordpress/i18n';
import CustomKeywordModal from './custom-keyword-modal';
import SettingsRow from '../../../components/settings-row';
import ConfigValues from '../../../es6/config-values';
import { connect } from 'react-redux';

class CustomKeywordPairs extends React.Component {
	constructor(props) {
		super(props);

		this.state = {
			addingPair: false,
			editingPair: null,
			pageNumber: 1,
		};

		this.perPage = 10;
	}

	render() {
		const { options, loading } = this.props;

		const pairs = this.textToPairs(options.customkey);
		const { pageNumber } = this.state;
		const totalCnt = pairs.length;
		const pagedPairs = pairs.slice(
			(pageNumber - 1) * this.perPage,
			pageNumber * this.perPage
		);

		return (
			<SettingsRow
				label={__('Custom Links', 'wds')}
				description={__(
					'Choose additional custom keywords you want to target, and where to link them to.',
					'wds'
				)}
				direction="column"
			>
				<>
					{totalCnt > 0 && (
						<table className="wds-keyword-pairs sui-table">
							<tbody>
								<tr>
									<th>{__('Keyword', 'wds')}</th>
									<th colSpan="2">
										{__(
											'Auto-Linked URL',
											'wds'
										)}
									</th>
								</tr>

								{pagedPairs.map((pair, idx) => {
									const absoluteIndex =
										(pageNumber - 1) * this.perPage + idx;
									return (
										<tr key={absoluteIndex}>
											<td>{pair.keyword}</td>
											<td>
												<a
													href={this.getAbsoluteUrl(
														pair.url
													)}
													title={pair.url}
												>
													{pair.url}
												</a>
											</td>
											<td>
												<Dropdown
													buttons={[
														<DropdownButton
															key={0}
															onClick={() =>
																this.startEditingPair(
																	absoluteIndex
																)
															}
															icon="sui-icon-pencil"
															text={__(
																'Edit',
																'wds'
															)}
														/>,
														<DropdownButton
															key={1}
															onClick={() =>
																this.deletePair(
																	absoluteIndex
																)
															}
															icon="sui-icon-trash"
															text={__(
																'Delete',
																'wds'
															)}
															red={true}
														/>,
													]}
													disabled={loading}
												/>

												{this.state.editingPair ===
													absoluteIndex && (
													<CustomKeywordModal
														keyword={pair.keyword}
														url={pair.url}
														editMode={true}
														onClose={() =>
															this.stopEditingPair()
														}
														onSave={(
															keyword,
															url
														) =>
															this.editPair(
																absoluteIndex,
																keyword,
																url
															)
														}
													/>
												)}
											</td>
										</tr>
									);
								})}
							</tbody>
						</table>
					)}

					<div className="wds-keyword-pairs-actions">
						<div className="wds-keyword-pair-new">
							<Button
								id="wds-keyword-pair-new-button"
								icon="sui-icon-plus"
								onClick={() => this.startAddingPair()}
								text={__('Add Link', 'wds')}
								disabled={loading}
							/>
						</div>

						{totalCnt > this.perPage && (
							<Pagination
								count={totalCnt}
								currentPage={this.state.pageNumber}
								perPage={this.perPage}
								onClick={(pgNum) => this.changePage(pgNum)}
							/>
						)}
					</div>

					{this.state.addingPair && (
						<CustomKeywordModal
							onClose={() => this.stopAddingPair()}
							onSave={(keyword, url) =>
								this.addPair(keyword, url)
							}
						/>
					)}
				</>
			</SettingsRow>
		);
	}

	changePage(pageNumber) {
		this.setState({ pageNumber });
	}

	getAbsoluteUrl(url) {
		if (url.indexOf('://') > 0 || url.indexOf('//') === 0) {
			return url;
		}
		const homeUrl = ConfigValues.get('home_url', 'admin');
		// Remove leading slash and append to home url.
		return homeUrl + url.replace(/^\/|\/$/g, '');
	}

	textToPairs(text) {
		const lines = text.split(/\n/);
		const pairs = [];
		lines.forEach((line) => {
			if (!line.includes(',')) {
				return;
			}
			const parts = line.split(',').map((part) => part.trim());
			pairs.push({
				keyword: parts.slice(0, -1).join(','),
				url: parts.slice(-1).pop(),
			});
		});

		return pairs;
	}

	pairsToText(pairs) {
		const lines = [];
		pairs.forEach((pair) => {
			const keyword = pair.keyword?.trim();
			const url = pair.url?.trim();

			if (keyword && url) {
				lines.push(keyword + ',' + url);
			}
		});

		return lines.join('\n');
	}

	startEditingPair(index) {
		this.setState({
			editingPair: index,
		});
	}

	editPair(index, keyword, url) {
		if (!keyword.trim() || !url.trim()) {
			return;
		}

		const { options, updateOption } = this.props;

		const pairs = this.textToPairs(options.customkey);

		pairs[index] = {
			keyword,
			url,
		};

		updateOption('customkey', this.pairsToText(pairs));

		this.setState({
			editingPair: null,
		});
	}

	stopEditingPair() {
		this.setState({
			editingPair: null,
		});
	}

	startAddingPair() {
		this.setState({
			addingPair: true,
		});
	}

	addPair(keyword, url) {
		if (!keyword.trim() || !url.trim()) {
			return;
		}

		const { options, updateOption } = this.props;

		const pairs = this.textToPairs(options.customkey);

		pairs.splice(0, 0, {
			keyword,
			url,
		});

		updateOption('customkey', this.pairsToText(pairs));

		this.setState({
			addingPair: false,
			pageNumber: 1,
		});
	}

	stopAddingPair() {
		this.setState({
			addingPair: false,
		});
	}

	deletePair(index) {
		const { options, updateOption } = this.props;

		const pairs = this.textToPairs(options.customkey);

		pairs.splice(index, 1);

		updateOption('customkey', this.pairsToText(pairs));
	}
}

const mapStateToProps = (state) => ({ ...state.autolinks });

const mapDispatchToProps = {
	updateOption: (key, value) => ({
		type: 'UPDATE_OPTION',
		key,
		value,
	}),
};

export default connect(mapStateToProps, mapDispatchToProps)(CustomKeywordPairs);
