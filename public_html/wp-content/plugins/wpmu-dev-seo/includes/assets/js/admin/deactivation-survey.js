import React from 'react';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import Modal from '../components/modal';
import Button from '../components/button';
import Notice from '../components/notices/notice';
import ConfigValues from '../es6/config-values';
import TextareaInputField from '../components/form-fields/textarea-input-field';
import TextInputField from '../components/form-fields/text-input-field';
import RequestUtil from '../utils/request-util';
import update from 'immutability-helper';
import fieldWithValidation from '../components/field-with-validation';
import { isNonEmpty, Validator } from '../utils/validators';
import { debounce } from 'lodash-es';
import $ from 'jQuery';
import Radio from '../components/input-fields/radio';

const opts = {
	'not-needed': __('I no longer need the plugin', 'wds'),
	switching: __("I'm switching to a different plugin", 'wds'),
	'not-work': __("I couldn't get the plugin to work", 'wds'),
	temporary: __("It's a temporary deactivation", 'wds'),
	other: __('Other', 'wds'),
};

const SwitchMsgField = fieldWithValidation(
	TextInputField,
	new Validator(isNonEmpty)
);

export default class DeactivationSurvey extends React.Component {
	static defaultProps = {
		from: '',
		data: {},
	};

	constructor(props) {
		super(props);

		this.state = {
			open: true,
			selected: false,
			messages: {},
			valid: true,
			submitting: false,
			skipping: false,
		};
	}

	handleSelect(selected) {
		this.setState({ selected }, () => {
			this.validateSelection();
		});
	}

	setMessage(key, content, valid) {
		this.setState(
			{
				messages: update(this.state.messages, {
					[key]: { $set: { content, valid } },
				}),
			},
			() => {
				this.validateSelection();
			}
		);
	}

	handleEvent(skipped) {
		const { selected, messages } = this.state;

		this.setState({
			submitting: !skipped,
			skipping: skipped,
		});

		const { from, data } = this.props;

		let reqData = {
			from,
			skipped,
		};

		if (!skipped) {
			reqData = {
				...reqData,
				selected,
			};

			if (messages[selected]?.valid) {
				reqData.message = messages[selected].content;
			}
		}

		RequestUtil.post(
			'smartcrawl_track_deactivate',
			ConfigValues.get('nonce', 'survey'),
			reqData
		);

		if (from === 'dashboard') {
			const debounced = debounce(() => {
				this.setState({ open: false }, () => {
					const wpmudevDashboardAdminPluginsPage = $('body').data(
						'wpmudevDashboardAdminPluginsPage'
					);

					if (!wpmudevDashboardAdminPluginsPage) {
						return;
					}

					wpmudevDashboardAdminPluginsPage.deactivate(data);
				});
			}, 1000);

			debounced();
		} else {
			window.location.href = data;
		}
	}

	handleClose() {
		this.setState({ open: false });
	}

	validateSelection() {
		const { selected, messages } = this.state;

		const states = { valid: true };

		if (selected === 'switching' && !messages?.switching?.valid) {
			states.valid = false;
		}

		this.setState(states);
	}

	render() {
		const { open } = this.state;

		if (!open) {
			return '';
		}

		const { selected, messages, submitting, skipping, valid } = this.state;

		return (
			<Modal
				id="wds-deactivation-survey"
				dialogClasses={{
					'sui-modal-lg': true,
					'sui-modal-sm': false,
				}}
				focusAfterClose="wpbody-content"
				title={__('Deactivate SmartCrawl?', 'wds')}
				description={__(
					'Before you go. Please take a moment to share your valuable insights on why you deactivated our plugin. Your feedback fuels our improvements.',
					'wds'
				)}
				footer={
					<>
						<Button
							color="blue"
							text={__('Submit & Deactivate', 'wds')}
							onClick={() => this.handleEvent(false)}
							loading={submitting}
							disabled={skipping || !valid}
						/>
						<Button
							color="ghost"
							text={__('Skip & Deactivate', 'wds')}
							onClick={() => this.handleEvent(true)}
							loading={skipping}
							disabled={submitting}
						/>
					</>
				}
				onClose={() => this.handleClose()}
			>
				<Notice
					type="info"
					message={createInterpolateElement(
						__(
							'<strong>Did you know you can use SmartCrawl with other SEO Plugins?</strong> You can <a>activate specific modules</a> that work seamlessly together with other SEO plugins.',
							'wds'
						),
						{
							strong: <strong />,
							a: (
								<a
									target="_blank"
									href={ConfigValues.get(
										'settings_url',
										'survey'
									)}
									rel="noreferrer"
								/>
							),
						}
					)}
				/>

				<div className="sui-row">
					{Object.keys(opts).map((key) => (
						<div className="sui-col-12" key={key}>
							<Radio
								label={opts[key]}
								checked={selected === key}
								disabled={submitting || skipping}
								onChange={() => this.handleSelect(key)}
							/>
							{selected === 'switching' &&
								key === 'switching' && (
									<SwitchMsgField
										onChange={(msg, _valid) =>
											this.setMessage(key, msg, _valid)
										}
										value={messages[key]?.content}
										isValid={messages[key]?.valid}
										placeholder={__(
											'Please kindly tell us which plugin you are switching to.',
											'wds'
										)}
										validateOnInit
									/>
								)}
							{selected === 'other' && key === 'other' && (
								<TextareaInputField
									onChange={(msg) =>
										this.setMessage(key, msg, true)
									}
									placeholder={__(
										'Please tell us why. (optional)',
										'wds'
									)}
									value={messages[key]?.content}
								/>
							)}
						</div>
					))}
				</div>
			</Modal>
		);
	}
}
