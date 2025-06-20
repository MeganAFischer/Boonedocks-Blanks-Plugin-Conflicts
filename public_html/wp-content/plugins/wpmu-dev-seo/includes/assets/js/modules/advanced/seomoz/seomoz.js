import React from 'react';
import { __ } from '@wordpress/i18n';
import { connect } from 'react-redux';
import Box from '../../../components/boxes/box';
import Button from '../../../components/button';
import Connect from './connect';
import { createInterpolateElement } from '@wordpress/element';
import RequestUtil from '../../../utils/request-util';
import ConfigValues from '../../../es6/config-values';
import NoticeUtil from '../../../utils/notice-util';
import SubmoduleBox from '../../../components/layout/submodule-box';

class Seomoz extends React.Component {
	constructor(props) {
		super(props);

		this.state = {
			reseting: false,
		};
	}

	handleReset() {
		const { updateOption, toggleLoading, updateProp } = this.props;

		this.setState({ reseting: true });
		toggleLoading();

		RequestUtil.post(
			`smartcrawl_update_moz_conn`,
			ConfigValues.get('nonce', 'admin'),
			{
				reset: 1,
			}
		)
			.then(() => {
				updateOption('access_id', '');
				updateOption('secret_key', '');
				updateProp('metrics', '');
			})
			.catch(() => {
				NoticeUtil.showErrorNotice(
					'smartcrawl-submodule-notice',
					__('Failed to reset API credentials.', 'wds')
				);
			})
			.finally(() => {
				this.setState({ reseting: false });
				toggleLoading();
			});
	}

	render() {
		return (
			<>
				{this.renderReport()}
				{this.renderSettings()}
			</>
		);
	}

	renderReport() {
		const { active } = this.props;

		if (!active) {
			return '';
		}

		const { loading, metrics } = this.props;

		if (!Object.keys(metrics).length) {
			return <Connect />;
		}

		const { attribution, urlmetrics } = metrics;
		const { reseting } = this.state;

		return (
			<Box title={__('Moz', 'wds')}>
				<p>
					{__(
						"Here's how your site stacks up against the competition as defined by Moz. You can also see individual stats per post in the post editor under the Moz module.",
						'wds'
					)}
				</p>

				<Button
					text={__('Reset API Credentials', 'wds')}
					disabled={loading}
					loading={reseting}
					onClick={() => this.handleReset()}
				/>

				<table className="sui-table">
					<thead>
						<tr>
							<th className="label">{__('Metric', 'wds')}</th>
							<th className="result">{__('Value', 'wds')}</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>
								<strong>{__('Domain mozRank', 'wds')}</strong>
								<br />
								{createInterpolateElement(
									__(
										'Measure of the mozRank <a>(?)</a> of the domain in the Linkscape index',
										'wds'
									),
									{
										a: (
											<a
												href="https://moz.com/learn/seo/mozrank"
												target="_blank"
												rel="noreferrer"
											/>
										),
									}
								)}
							</td>
							<td>
								{__('10-point score:', 'wds')}
								&nbsp;
								<a
									href={attribution}
									target="_blank"
									rel="noreferrer"
								>
									{urlmetrics.fmrp ? urlmetrics.fmrp : ''}
								</a>
								<br />
								{__('Raw score:', 'wds')}&nbsp;
								<a
									href={attribution}
									target="_blank"
									rel="noreferrer"
								>
									{urlmetrics.fmrr ? urlmetrics.fmrr : ''}
								</a>
							</td>
						</tr>
						<tr>
							<td>
								{createInterpolateElement(
									__(
										'<strong>Domain Authority</strong> <a>(?)</a>',
										'wds'
									),
									{
										strong: <strong />,
										a: (
											<a
												href="https://moz.com/learn/seo/domain-authority"
												target="_blank"
												rel="noreferrer"
											/>
										),
									}
								)}
							</td>
							<td>
								<a
									href={attribution}
									target="_blank"
									rel="noreferrer"
								>
									{urlmetrics.pda ? urlmetrics.pda : ''}
								</a>
							</td>
						</tr>
						<tr>
							<td>
								<strong>
									{__(
										'External Links to Homepage',
										'wds'
									)}
								</strong>
								<br />
								{createInterpolateElement(
									__(
										'The number of external (from other subdomains), juice passing links <a>(?)</a> to the target URL in the Linkscape index',
										'wds'
									),
									{
										a: (
											<a
												href="https://moz.com/learn/seo/external-link"
												target="_blank"
												rel="noreferrer"
											/>
										),
									}
								)}
							</td>
							<td>
								<a
									href={attribution}
									target="_blank"
									rel="noreferrer"
								>
									{urlmetrics.ueid ? urlmetrics.ueid : ''}
								</a>
							</td>
						</tr>
						<tr>
							<td>
								<strong>
									{__('Links to Homepage', 'wds')}
								</strong>
								<br />
								{createInterpolateElement(
									__(
										'The number of internal and external, juice and non-juice passing links <a>(?)</a> to the target URL in the Linkscape index',
										'wds'
									),
									{
										a: (
											<a
												href="https://moz.com/learn/seo/internal-link"
												target="_blank"
												rel="noreferrer"
											/>
										),
									}
								)}
							</td>
							<td>
								<a
									href={attribution}
									target="_blank"
									rel="noreferrer"
								>
									{urlmetrics.uid ? urlmetrics.uid : ''}
								</a>
							</td>
						</tr>
						<tr>
							<td>
								<strong>{__('Homepage mozRank', 'wds')}</strong>
								<br />
								{createInterpolateElement(
									__(
										'Measure of the mozRank <a>(?)</a> of the homepage URL in the Linkscape index',
										'wds'
									),
									{
										a: (
											<a
												href="https://moz.com/learn/seo/mozrank"
												target="_blank"
												rel="noreferrer"
											/>
										),
									}
								)}
							</td>
							<td>
								{__('10-point score:', 'wds')}
								&nbsp;
								<a
									href={attribution}
									target="_blank"
									rel="noreferrer"
								>
									{urlmetrics.umrp ? urlmetrics.umrp : ''}
								</a>
								<br />
								{__('Raw score:', 'wds')}&nbsp;
								<a
									href={attribution}
									target="_blank"
									rel="noreferrer"
								>
									{urlmetrics.umrr ? urlmetrics.umrr : ''}
								</a>
							</td>
						</tr>
						<tr>
							<td>
								{createInterpolateElement(
									__(
										'<strong>Homepage Authority</strong> <a>(?)</a>',
										'wds'
									),
									{
										strong: <strong />,
										a: (
											<a
												href="https://moz.com/learn/seo/page-authority"
												target="_blank"
												rel="noreferrer"
											/>
										),
									}
								)}
							</td>
							<td>
								<a
									href={attribution}
									target="_blank"
									rel="noreferrer"
								>
									{urlmetrics.upa ? urlmetrics.upa : ''}
								</a>
							</td>
						</tr>
					</tbody>
					<tfoot>
						<tr>
							<th className="label">
								{__('Metric', 'wds')}
							</th>
							<th className="result">
								{__('Value', 'wds')}
							</th>
						</tr>
					</tfoot>
				</table>
			</Box>
		);
	}

	renderSettings() {
		const { active } = this.props;

		return (
			<SubmoduleBox
				name="seomoz"
				title={
					active
						? __('Settings', 'wds')
						: __('Moz', 'wds')
				}
				activateProps={{
					message: __(
						'Moz provides reports that tell you how your site stacks up against the competition with all of the important SEO measurement tools - ranking, links, and much more.',
						'wds'
					),
				}}
				deactivateProps={{
					description: __(
						'No longer need MOZ? Deactivate MOZ here. This will also reset your MOZ credentials.',
						'wds'
					),
				}}
				disableFooter
			></SubmoduleBox>
		);
	}
}

const mapStateToProps = (state) => ({ ...state.seomoz });

const mapDispatchToProps = {
	updateOption: (key, value) => ({
		type: 'UPDATE_OPTION',
		key,
		value,
	}),
	updateProp: (key, value) => ({
		type: 'UPDATE_PROP',
		key,
		value,
	}),
	toggleLoading: () => ({
		type: 'TOGGLE_LOADING',
	}),
};

export default connect(mapStateToProps, mapDispatchToProps)(Seomoz);
