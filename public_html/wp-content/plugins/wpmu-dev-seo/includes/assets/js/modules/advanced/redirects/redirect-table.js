import React from 'react';
import { __, _n, sprintf } from '@wordpress/i18n';
import Button from '../../../components/button';
import RedirectItem from './redirect-item';
import RedirectModal from './redirect-modal';
import { pickBy } from 'lodash-es';
import update from 'immutability-helper';
import BulkUpdateModal from './bulk-update-modal';
import Pagination from '../../../components/navigations/pagination';
import PaginationUtil from '../../../utils/pagination-util';
import SUI from 'SUI';
import UrlUtil from '../../../utils/url-util';
import RequestUtil from '../../../utils/request-util';
import ConfirmationModal from '../../../components/confirmation-modal';
import { ImportModal } from './import-modal';
import FileUtil from '../../../utils/file-util';
import { DateTime } from 'luxon';
import { createInterpolateElement } from '@wordpress/element';
import Search from '../../../components/search';
import memoizeOne from 'memoize-one';
import Notice from '../../../components/notices/notice';
import ConfigValues from '../../../es6/config-values';
import { connect } from 'react-redux';
import {
	getFullUrl,
	getRequestData,
	isNonRedirectType,
	populateDestination,
} from '../../../utils/redirect-utils';
import $ from 'jQuery';
import Box from '../../../components/boxes/box';

const isNewFeature = ConfigValues.get('new_feature_status', 'admin') !== '4';

const homeUrl = ConfigValues.get('home_url', 'admin').replace(/\/$/, '');
const nonce = ConfigValues.get('nonce', 'admin') || {};

class RedirectTable extends React.Component {
	constructor(props) {
		super(props);

		this.state = {
			redirects: this.props.redirects || {},
			keyword: '',
			bulkItems: new Set(),
			saving: false,
			deleting: false,
			deletingAll: false,
			loading: false,
			importing: false,
			exporting: false,
			currentPage: 1,
			perPage: 10,
		};

		this.filterMemoized = memoizeOne((keyword, _redirects) =>
			this.filterRedirects(keyword, _redirects)
		);

		this.tableRef = React.createRef();
	}

	componentDidMount() {
		if (this.props.active) {
			this.maybeStartSaving();
		}
	}

	render() {
		if (!this.props.active) {
			return '';
		}

		const {
			deletingRule,
			updateDeletingRule,
			deleteRule,
			bulkUpdating,
			updateStore,
		} = this.props;

		const {
			bulkItems,
			loading,
			saving,
			deleting,
			deletingAll,
			currentPage,
			importing,
			exporting,
			keyword,
			perPage,
		} = this.state;

		const filteredRedirects = this.getFilteredRedirects();
		const filteredTotal = this.objectLength(filteredRedirects);
		const page = this.getRedirectsPage();
		const pageLength = this.objectLength(page);
		const bulkCount = bulkItems.size;
		const allChecked = pageLength > 0 && bulkCount === pageLength;

		return (
			<Box
				title={__('URL Redirection', 'wds')}
				className="wds-redirects"
				headerRight={
					<>
						<Button
							text={__('Import', 'wds')}
							ghost
							icon="sui-icon-upload-cloud"
							disabled={loading}
							onClick={() => this.startImporting()}
						/>

						<Button
							text={__('Export', 'wds')}
							ghost
							icon="sui-icon-cloud-migration"
							disabled={loading}
							loading={exporting}
							onClick={() => this.exportRedirects()}
						/>
					</>
				}
			>
				<p>
					{__(
						'Automatically redirect traffic from one URL to another. Use this tool if you have changed a page’s URL and wish to keep traffic flowing to the new page.',
						'wds'
					)}
				</p>

				{!!isNewFeature && (
					<Notice
						type="info"
						message={
							<>
								{createInterpolateElement(
									__(
										'<strong>New</strong>: You can now add Geolocation-based rules to redirects. This will ensure users are redirected to the most relevant content based on their locations. <a>Add a new redirect</a> to set location rules.',
										'wds'
									),
									{
										strong: <strong />,
										a: (
											<a
												href="#"
												onClick={() =>
													this.startSaving()
												}
											/>
										),
									}
								)}
								<span className="wds-new-feature-status" />
							</>
						}
					/>
				)}

				<div className="sui-box-builder">
					<div className="sui-box-builder-header">
						<div className="sui-box-builder-actions">
							<Button
								text={__('Add Redirect', 'wds')}
								color="purple"
								icon="sui-icon-plus"
								onClick={() => this.startSaving()}
							/>

							{filteredTotal > 0 && (
								<Button
									text={__('Delete All', 'wds')}
									color="red"
									ghost
									icon="sui-icon-trash"
									onClick={() => this.startDeletingAll()}
								/>
							)}
						</div>

						{filteredTotal > 0 && (
							<Pagination
								count={filteredTotal}
								currentPage={currentPage}
								perPage={perPage}
								perPageOptions={[10, 20, 50, 100]}
								onClick={(pageNumber) =>
									this.changePage(pageNumber)
								}
								onPerPageChange={(pp) =>
									this.handlePerPageChange(pp)
								}
							/>
						)}
					</div>

					<div className="sui-box-builder-body">
						<div className="wds-redirect-controls">
							{filteredTotal > 0 && (
								<>
									<label className="sui-checkbox">
										<input
											type="checkbox"
											checked={allChecked}
											onChange={(e) =>
												this.toggleAll(e.target.checked)
											}
										/>
										<span aria-hidden="true" />
									</label>

									<Button
										text={__('Update', 'wds')}
										onClick={() =>
											updateStore({
												bulkUpdating: true,
												bulkType: true,
											})
										}
										disabled={!bulkCount}
									/>

									<Button
										text={__('Delete', 'wds')}
										onClick={() => this.startBulkDeleting()}
										disabled={!bulkCount}
									/>
								</>
							)}
							<Search
								placeholder={__('Search Redirects', 'wds')}
								onChange={(kw) => this.handleSearch(kw)}
							/>
						</div>

						<div className="sui-builder-fields" ref={this.tableRef}>
							{filteredTotal > 0 && (
								<div className="wds-redirect-item wds-redirect-item-columns">
									<div className="wds-redirect-item-checkbox" />

									<div className="wds-redirect-item-source">
										<small>
											<strong>{__('From', 'wds')}</strong>
										</small>
									</div>

									<div className="wds-redirect-item-destination">
										<small>
											<strong>{__('To', 'wds')}</strong>
										</small>
									</div>

									<div className="wds-redirect-item-options">
										<small>
											<strong>{__('Type', 'wds')}</strong>
										</small>
									</div>

									<div className="wds-redirect-item-dropdown" />
								</div>
							)}

							{Object.keys(page).map((id) => (
								<RedirectItem
									{...page[id]}
									key={id}
									selected={bulkItems.has(id)}
									onToggle={(selected) =>
										this.toggleItem(id, selected)
									}
									onEdit={() => this.startSaving(id)}
									onDelete={() => this.startDeleting(id)}
								/>
							))}
						</div>

						{keyword && !filteredTotal && (
							<p>
								{createInterpolateElement(
									sprintf(
										// translators: %s: Keyword.
										__(
											'No results found for the keyword <strong>%s</strong>.',
											'wds'
										),
										keyword
									),
									{ strong: <strong /> }
								)}
							</p>
						)}

						<Button
							id="wds-add-redirect-dashed-button"
							dashed={true}
							icon="sui-icon-plus"
							text={__('Add Redirect', 'wds')}
							onClick={() => this.startSaving()}
						/>

						{!filteredTotal && (
							<p className="wds-no-redirects-message">
								<small>
									{__(
										'You can add as many redirects as you like. Add your first above!',
										'wds'
									)}
								</small>
							</p>
						)}
					</div>

					{filteredTotal > 0 && (
						<div className="sui-box-builder-footer">
							<Pagination
								count={filteredTotal}
								currentPage={currentPage}
								perPage={perPage}
								perPageOptions={[10, 20, 50, 100]}
								onClick={(pageNumber) =>
									this.changePage(pageNumber)
								}
								onPerPageChange={(pp) =>
									this.handlePerPageChange(pp)
								}
							/>
						</div>
					)}
				</div>

				{saving && (
					<RedirectModal
						onSave={() => this.handleSave()}
						onClose={() => this.stopSaving()}
					/>
				)}

				{deleting && (
					<ConfirmationModal
						id="wds-delete-redirect-modal"
						title={_n(
							'Are you sure?',
							'Bulk Delete Redirect?',
							deleting.length,
							'wds'
						)}
						description={
							deleting.length > 1
								? sprintf(
										// translators: %s: number of items being deleted.
										__(
											'Are you sure you want to delete the %s selected redirects? This action is irreversible.',
											'wds'
										),
										deleting.length
								  )
								: __(
										'Are you sure you want to delete this redirect? This action is irreversible.',
										'wds'
								  )
						}
						loading={loading}
						onClose={() => this.stopDeleting()}
						onDelete={() => this.handleDelete()}
					/>
				)}

				{bulkUpdating && (
					<BulkUpdateModal
						count={bulkItems.size}
						onSave={() => this.handleBulkUpdate()}
						onClose={() => updateStore()}
					/>
				)}

				{deletingAll && (
					<ConfirmationModal
						id="wds-delete-all-modal"
						title={__('Are you sure?', 'wds')}
						description={__(
							'Are you sure you want to delete all redirects? This action is irreversible.',
							'wds'
						)}
						loading={loading}
						onClose={() => this.stopDeletingAll()}
						onDelete={() => this.handleDeleteAll()}
					/>
				)}

				{deletingRule !== false && (
					<ConfirmationModal
						id="geo-redirect-removing"
						title={__('Are you sure?', 'wds')}
						description={__(
							'Are you sure you want to delete this rule? This action is irreversible.',
							'wds'
						)}
						onClose={() => updateDeletingRule(false)}
						onDelete={() => deleteRule(deletingRule)}
					/>
				)}

				{importing && (
					<ImportModal
						loading={loading}
						onClose={() => this.stopImporting()}
						onImport={(file) => this.importRedirects(file)}
					/>
				)}
			</Box>
		);
	}

	exportRedirects() {
		this.setState(
			{
				exporting: true,
				loading: true,
			},
			() => {
				RequestUtil.post('smartcrawl_export_redirects', nonce)
					.then((data) => {
						const host = UrlUtil.getUrlHost(homeUrl);
						const date = DateTime.now().toFormat('dd-LL-y');

						FileUtil.triggerFileDownload(
							data.content,
							`smartcrawl-redirects-${host}-${date}.json`
						);
					})
					.catch((errorMessage) => {
						this.showErrorNotice(errorMessage);
					})
					.finally(() => {
						this.setState({
							exporting: false,
							loading: false,
						});
					});
			}
		);
	}

	startImporting() {
		this.setState({ importing: true });
	}

	stopImporting() {
		this.setState({ importing: false });
	}

	importRedirects(file) {
		this.setState({ loading: true }, () => {
			RequestUtil.uploadFile('smartcrawl_import_redirects', nonce, file)
				.then((data) => {
					const insertedCount = data.count;
					this.showSuccessNotice(
						sprintf(
							// translators: %d: Inserted count.
							_n(
								'%d redirect inserted successfully!',
								'%d redirects inserted successfully!',
								insertedCount,
								'wds'
							),
							insertedCount
						)
					);
					this.setState({ redirects: data.redirects }, () => {
						this.setState({
							currentPage: this.getPageCount(),
						});
					});
				})
				.catch((message) => {
					this.showErrorNotice(message);
				})
				.finally(() => {
					this.setState({
						loading: false,
						importing: false,
					});
				});
		});
	}

	changePage(pageNumber, toTop = true) {
		this.setState(
			{
				currentPage: pageNumber,
				bulkItems: new Set(),
			},
			() => {
				const scrollPos = $(window).scrollTop();
				const $target = $(this.tableRef.current);

				if (toTop) {
					if (
						scrollPos < $target.offset().top - $target.height() ||
						scrollPos > $target.offset().top + $target.height()
					) {
						$([document.documentElement, document.body]).animate(
							{
								scrollTop: $target.offset().top - 50,
							},
							200
						);
					}
				} else {
					const $lastChild = $target.find(
						'.wds-redirect-item:last-child'
					);

					if (!$lastChild.length) {
						return;
					}

					if (
						scrollPos < $lastChild.offset().top ||
						scrollPos > $lastChild.offset().top + $target.height()
					) {
						$([document.documentElement, document.body]).animate(
							{
								scrollTop: $lastChild.offset().top - 50,
							},
							200
						);
					}
				}
			}
		);
	}

	handlePerPageChange(perPage) {
		this.setState({ perPage, currentPage: 1 });
	}

	ajaxPost(action, data) {
		return RequestUtil.post(action, nonce, data);
	}

	validate(data) {
		const source = data.source;

		if (
			Object.values(this.state.redirects).filter((redirect) => {
				return (
					(!data.id || redirect.id !== data.id) &&
					getFullUrl(redirect.source) ===
						getFullUrl(encodeURI(decodeURI(source)))
				);
			}).length > 0
		) {
			return __('That URL already exists, please try again.', 'wds');
		}

		if (isNonRedirectType(data.type)) {
			return;
		}

		if (!data.destination) {
			return;
		}

		const chain = this.findChainDFS(
			getFullUrl(data.destination.url || data.destination),
			getFullUrl(source),
			data.id
		);

		if (chain.length) {
			return sprintf(
				/* translators: 1: redirect destination, 2: redirect source */
				__(
					'Redirect chain from <strong>%1$s</strong> to <strong>%2$s</strong> already exists.',
					'wds'
				),
				data.destination.url || data.destination,
				data.source
			);
		}
	}

	findChainDFS(source, destination, id, visited = []) {
		visited.push(source);

		// Finds all redirects where the source matches the current source.
		const nextRedirects = Object.values(this.state.redirects).filter(
			(redirect) =>
				(!id || redirect.id !== id) &&
				getFullUrl(redirect.source) === source
		);

		for (const redirect of nextRedirects) {
			if (
				getFullUrl(redirect.destination.url || redirect.destination) ===
				destination
			) {
				// Found the destination, return the chain
				return [source, destination];
			} else if (
				!visited.includes(
					getFullUrl(redirect.destination.url || redirect.destination)
				)
			) {
				// Recursively search for the destination in the next object
				const subChain = this.findChainDFS(
					getFullUrl(
						redirect.destination.url || redirect.destination
					),
					destination,
					visited
				);

				if (subChain.length > 0) {
					// Add the current source to the sub-chain and return it
					subChain.unshift(source);
					return subChain;
				}
			}
		}

		// If the destination is not found, return an empty array
		return [];
	}

	startSaving(id = true) {
		this.setState({ saving: id });

		let data;

		if (id && id !== true) {
			data = this.state.redirects[id];
		} else if (UrlUtil.getQueryParam('source')) {
			data = { source: UrlUtil.getQueryParam('source') };
			UrlUtil.removeQueryParam('add_redirect');
			UrlUtil.removeQueryParam('source');
		} else {
			data = {};
		}

		data.maxmindUpdate = this.props.maxmindUpdate;

		this.props.updateStore(data);
	}

	handleSave() {
		let data = getRequestData(this.props);

		const msg = this.validate(data);

		if (msg) {
			this.showErrorNotice(msg);
			return;
		}

		data = populateDestination(data);

		this.props.updateLoading(true);

		this.ajaxPost('smartcrawl_save_redirect', data)
			.then((resp) => {
				this.setState(
					{
						redirects: update(this.state.redirects, {
							[resp.id]: {
								$set: resp,
							},
						}),
						saving: false,
					},
					() => {
						if (!this.props.id) {
							this.changePage(this.getPageCount(), false);
						}
					}
				);
				this.showSuccessNotice(
					__('The redirect has been updated.', 'wds')
				);
			})
			.catch((message) => {
				this.showErrorNotice(message || '');
			})
			.finally(() => {
				this.props.updateLoading(false);
			});
	}

	stopSaving() {
		this.removeQueryParam();
		this.setState({ saving: false });
	}

	handleBulkUpdate() {
		let data = getRequestData(this.props);

		const ids = Array.from(this.state.bulkItems);

		for (let i = 0; i < ids.length; i++) {
			const msg = this.validate({
				...data,
				id: this.state.redirects[ids[i]].id,
				source: this.state.redirects[ids[i]].source,
			});

			if (msg) {
				this.showErrorNotice(msg);
				return;
			}
		}

		data = populateDestination(data);

		data.ids = ids;

		delete data.id;
		delete data.source;
		delete data.options;

		const { updateLoading, updateStore } = this.props;

		updateLoading(true);

		this.ajaxPost('smartcrawl_bulk_update_redirects', data)
			.then((resp) => {
				const spec = {};
				ids.forEach((id) => {
					spec[id] = { $set: { ...resp[id] } };
				});
				this.setState({
					redirects: update(this.state.redirects, spec),
					bulkItems: new Set(),
				});
				updateStore();
				this.showSuccessNotice(
					__('The redirects have been updated.', 'wds')
				);
			})
			.catch((message) => {
				this.showErrorNotice(
					message || __('Failed to update redirects.', 'wds')
				);
			})
			.finally(() => {
				updateLoading(false);
			});
	}

	startDeleting(id) {
		this.setState({ deleting: [id] });
	}

	startBulkDeleting() {
		const ids = Array.from(this.state.bulkItems);
		this.setState({ deleting: ids });
	}

	stopDeleting() {
		this.setState({ deleting: false });
	}

	handleDelete() {
		const ids = this.state.deleting;
		this.setState({ loading: true });
		this.ajaxPost('smartcrawl_delete_redirect', { ids })
			.then(() => {
				const bulkItemSet = update(this.state.bulkItems, {
					$remove: ids,
				});

				this.setState(
					{
						redirects: update(this.state.redirects, {
							$unset: ids,
						}),
					},
					() => {
						this.setState({
							deleting: false,
							loading: false,
							currentPage: this.newPageNumberAfterDeletion(),
							bulkItems: bulkItemSet,
						});
					}
				);

				this.showSuccessNotice(
					_n(
						'The redirect has been removed.',
						'The redirects have been removed.',
						ids.length,
						'wds'
					)
				);
			})
			.catch((message) => {
				this.showErrorNotice(message);
			});
	}

	startDeletingAll() {
		this.setState({ deletingAll: true });
	}

	stopDeletingAll() {
		this.setState({ deletingAll: false });
	}

	handleDeleteAll() {
		this.setState({ loading: true });

		this.ajaxPost('smartcrawl_delete_redirect')
			.then(() => {
				this.setState(
					{
						redirects: {},
					},
					() => {
						this.setState({
							deletingAll: false,
							currentPage: this.newPageNumberAfterDeletion(),
						});
					}
				);

				this.showSuccessNotice(
					__('All redirects have been removed.', 'wds')
				);
			})
			.catch((message) => {
				this.showErrorNotice(message);
			})
			.finally(() => {
				this.setState({ loading: false });
			});
	}

	toggleItem(id, selected) {
		const set = new Set(this.state.bulkItems);
		if (selected) {
			set.add(id);
		} else {
			set.delete(id);
		}
		this.setState({
			bulkItems: set,
		});
	}

	toggleAll(selected) {
		let bulkItems;
		if (selected) {
			bulkItems = Object.keys(this.getRedirectsPage());
		} else {
			bulkItems = [];
		}
		this.setState({
			bulkItems: new Set(bulkItems),
		});
	}

	getPageCount() {
		return PaginationUtil.getPageCount(
			this.objectLength(this.getFilteredRedirects()),
			this.state.perPage
		);
	}

	getRedirectsPage() {
		return PaginationUtil.getPage(
			this.getFilteredRedirects(),
			this.state.currentPage,
			this.state.perPage
		);
	}

	newPageNumberAfterDeletion() {
		const currentPage = this.state.currentPage;
		return currentPage > this.getPageCount()
			? currentPage - 1
			: currentPage;
	}

	objectLength(obj) {
		return Object.keys(obj).length;
	}

	showNotice(message, type = 'success') {
		const icons = {
			error: 'warning-alert',
			info: 'info',
			warning: 'warning-alert',
			success: 'check-tick',
		};

		SUI.closeNotice('smartcrawl-submodule-notice');
		SUI.openNotice(
			'smartcrawl-submodule-notice',
			'<p>' + message + '</p>',
			{
				type,
				icon: icons[type],
				dismiss: { show: false },
			}
		);
	}

	showSuccessNotice(message) {
		this.showNotice(message, 'success');
	}

	showErrorNotice(message) {
		this.showNotice(
			message
				? message
				: __(
						'An error occurred. Please reload the page and try again!',
						'wds'
				  ),
			'error'
		);
	}

	maybeStartSaving() {
		if (UrlUtil.getQueryParam('add_redirect') === '1') {
			this.startSaving();
		}
	}

	removeQueryParam() {
		UrlUtil.removeQueryParam('add_redirect');
	}

	handleSearch(keyword) {
		this.setState({
			keyword,
			bulkItems: new Set(),
			currentPage: 1,
		});
	}

	getFilteredRedirects() {
		return this.filterMemoized(this.state.keyword, this.state.redirects);
	}

	filterRedirects(keyword, _redirects) {
		return pickBy(_redirects, (redirect) => {
			return this.redirectMatchesKeyword(keyword, redirect);
		});
	}

	redirectMatchesKeyword(keyword, redirect) {
		if (!keyword) {
			return true;
		}

		let matches = false;
		['source', 'destination', 'title'].some((prop) => {
			if (!redirect || !redirect.hasOwnProperty(prop)) {
				return false;
			}

			let propertyValue =
				typeof redirect[prop] === 'string'
					? redirect[prop]
					: redirect[prop]?.url;

			if (!propertyValue) {
				return false;
			}

			propertyValue = propertyValue.toLowerCase();

			const lowerCaseKeyword = keyword.toLowerCase();

			if (propertyValue.indexOf(lowerCaseKeyword) !== -1) {
				matches = true;
				return true;
			}

			return false;
		});

		return matches;
	}
}

const mapStateToProps = (state) => ({
	...state,
});

const mapDispatchToProps = {
	updateStore: (data) => ({
		type: 'UPDATE_STORE',
		payload: data,
	}),
	updateLoading: (loading) => ({
		type: 'UPDATE_LOADING',
		payload: { loading },
	}),
	updateDeletingRule: (index) => ({
		type: 'DELETING_RULE',
		payload: index,
	}),
	deleteRule: (index) => ({
		type: 'DELETE_RULE',
		payload: index,
	}),
};

export default connect(mapStateToProps, mapDispatchToProps)(RedirectTable);
