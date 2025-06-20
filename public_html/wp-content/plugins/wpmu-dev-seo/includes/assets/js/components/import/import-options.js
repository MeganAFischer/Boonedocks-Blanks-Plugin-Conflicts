import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import Button from '../button';
import ImportItem from './import-item';
import Notice from '../notices/notice';
import classnames from 'classnames';

export default class ImportOptions extends React.Component {
	static defaultProps = {
		sourceName: '',
		options: [],
		onStart: () => false,
		onChange: () => false,
	};

	constructor(props) {
		super(props);

		this.state = {
			advOpened: false,
		};
	}

	render() {
		const { sourceName, onChange, options } = this.props;

		return (
			<React.Fragment>
				<p>
					{sprintf(
						/* translators: %s: source plugin name. */
						__(
							"Choose what you'd like to import from %s.",
							'wds-texdomain'
						),
						sourceName
					)}
				</p>

				{options
					.filter((option) => !option.advanced)
					.map((option, index) => (
						<ImportItem
							key={index}
							onChange={onChange}
							{...option}
						/>
					))}
				<div
					className={classnames('wds-advanced-import-options', {
						open: this.state.advOpened,
					})}
				>
					{/* eslint-disable-next-line jsx-a11y/click-events-have-key-events,jsx-a11y/no-static-element-interactions */}
					<span
						onClick={() =>
							this.setState({
								advOpened: !this.state.advOpened,
							})
						}
					>
						{__('Advanced', 'wds-texdomain')}
					</span>
					{this.state.advOpened && (
						<div className="wds-advanced-import-options-inner">
							{options
								.filter((option) => option.advanced)
								.map((option, index) => (
									<ImportItem
										key={index}
										onChange={onChange}
										{...option}
									/>
								))}
						</div>
					)}
				</div>
				<div className="wds-import-footer">
					<div className="cf">
						<Button
							className="wds-import-main-action wds-import-start"
							color="blue"
							text={__('Begin Import', 'wds-texdomain')}
							onClick={() => this.handleBegin()}
						/>
					</div>
					<Notice
						className="wds-notice"
						type="info"
						message={__(
							'Note: Importing can take a while if you have a large amount of content on your website.',
							'wds-texdomain'
						)}
					/>
				</div>
			</React.Fragment>
		);
	}

	handleBegin() {
		const options = {};

		this.props.options.forEach((option) => {
			options[option.name] = option.checked ? 1 : 0;
		});

		this.props.onStart(options);
	}
}
