import React from 'react';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import SideTabsField from '../../../components/side-tabs-field';
import { connect } from 'react-redux';
import { TitleField } from '../../../utils/redirect-utils';

class RedirectEditAdvanced extends React.Component {
	render() {
		const { title, options, loading, updateTitle, updateOptions } =
			this.props;

		const isRegex = options.includes('regex');

		return (
			<>
				<TitleField
					label={__('Label (Optional)', 'wds')}
					description={__(
						'Use labels to differentiate long or similar URLs.',
						'wds'
					)}
					value={title}
					placeholder={__('E.g. Press release', 'wds')}
					onChange={(updatedTitle, valid) =>
						updateTitle(updatedTitle, valid)
					}
					disabled={loading}
				/>
				<SideTabsField
					label={__('Regular Expression', 'wds')}
					description={createInterpolateElement(
						__(
							'Choose whether the strings entered into the Redirect From and Redirect To fields above should be treated as plain text URLs or regular expressions (Regex). Note that only valid regular expressions are allowed. <a>Learn more</a> about Regex.',
							'wds'
						),
						{
							a: (
								<a
									target="_blank"
									href="https://wpmudev.com/docs/wpmu-dev-plugins/smartcrawl/#about-regex-redirects"
									rel="noreferrer"
								/>
							),
						}
					)}
					tabs={{
						0: __('Plain Text', 'wds'),
						1: __('Regex', 'wds'),
					}}
					value={isRegex ? '1' : '0'}
					onChange={(checked) =>
						updateOptions('regex', checked === '1')
					}
				/>
			</>
		);
	}
}

const mapStateToProps = (state) => ({ ...state });

const mapDispatchToProps = {
	updateTitle: (title, valid) => ({
		type: 'UPDATE_TITLE',
		payload: { title, valid },
	}),
	updateOptions: (option, value) => ({
		type: 'UPDATE_OPTIONS',
		payload: { option, value },
	}),
};

export default connect(
	mapStateToProps,
	mapDispatchToProps
)(RedirectEditAdvanced);
