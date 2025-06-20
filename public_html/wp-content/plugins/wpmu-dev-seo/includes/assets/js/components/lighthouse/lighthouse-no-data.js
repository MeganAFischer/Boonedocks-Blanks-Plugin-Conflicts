import React from 'react';
import LighthouseProgressModal from './lighthouse-progress-modal';
import { createInterpolateElement } from '@wordpress/element';
import DisabledComponent from '../disabled-component';
import { __, sprintf } from '@wordpress/i18n';
import Button from '../button';
import RequestUtil from '../../utils/request-util';
import ConfigValues from '../../es6/config-values';
import VerticalTab from '../vertical-tab';
import UrlUtil from '../../utils/url-util';

const isMember = ConfigValues.get('is_member', 'admin') === '1';

export default class LighthouseNoData extends React.Component {
	static defaultProps = {
		startTime: '',
		image: '',
	};

	constructor(props) {
		super(props);

		this.state = {
			openDialog: false,
			progress: 0,
			statusMessage: '',
		};
	}

	componentDidMount() {
		if (!!this.props.startTime) {
			this.handleProgress();
		}
	}

	render() {
		const { startTime, image } = this.props;
		const { openDialog, progress, statusMessage } = this.state;

		const isActive =
			!UrlUtil.getQueryParam('tab') ||
			UrlUtil.getQueryParam('tab') === 'tab_lighthouse';

		return (
			<VerticalTab
				id="tab_lighthouse"
				title={__('Get Started', 'wds')}
				isActive={isActive}
			>
				{openDialog && (
					<LighthouseProgressModal
						progress={progress}
						statusMessage={statusMessage}
						onClose={() => this.setState({ openDialog: false })}
					/>
				)}
				<DisabledComponent
					imagePath={image}
					message={createInterpolateElement(
						sprintf(
							// translators: %s: plugin title
							__(
								'Let’s find out what can be improved!<br/><strong>%s</strong> will run a quick SEO test against your Homepage, and then give you the tools to drastically improve your SEO.',
								'wds'
							),
							ConfigValues.get('plugin_title', 'admin')
						),
						{
							br: <br />,
							strong: <strong />,
						}
					)}
					button={
						<Button
							color="blue"
							text={__('Test My Homepage', 'wds')}
							disabled={!!startTime}
							onClick={() => this.handleProgress()}
						/>
					}
					inner
				/>
			</VerticalTab>
		);
	}

	handleProgress() {
		this.setState({ openDialog: true }, () => {
			this.updateProgress();
		});
	}

	updateProgress() {
		let progress = 0,
			remoteCallPending = false;

		const interval = setInterval(() => {
			if (remoteCallPending) {
				return;
			}

			progress++;

			const visibleProgress = progress > 99 ? 99 : progress;

			this.setState({
				progress: visibleProgress,
				statusMessage:
					visibleProgress === 75
						? __(
								'Analyzing data and preparing report…',
								'wds'
						  )
						: visibleProgress === 3
						? __('Running SEO test…', 'wds')
						: this.state.statusMessage,
			});

			if (progress === 1 || (progress > 30 && progress % 9 === 0)) {
				remoteCallPending = true;
				RequestUtil.post(
					'wds-lighthouse-run',
					ConfigValues.get('nonce', 'lighthouse')
				)
					.then((data) => {
						if ((data || {}).finished) {
							clearInterval(interval);

							this.setState(
								{
									progress: 100,
									statusMessage: __(
										'Refreshing data. Please wait…',
										'wds'
									),
								},
								() => {
									window.location.reload();
								}
							);
						}
					})
					.finally(function () {
						remoteCallPending = false;
					});
			}
		}, 200);
	}
}
