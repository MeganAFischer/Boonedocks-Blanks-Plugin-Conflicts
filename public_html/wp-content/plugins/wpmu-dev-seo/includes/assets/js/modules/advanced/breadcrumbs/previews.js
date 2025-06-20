import React, { createRef } from 'react';
import { __ } from '@wordpress/i18n';
import SettingsRow from '../../../components/settings-row';
import { createInterpolateElement } from '@wordpress/element';
import $ from 'jQuery';
import { connect } from 'react-redux';
import ConfigValues from '../../../es6/config-values';

class Previews extends React.Component {
	constructor(props) {
		super(props);

		this.state = {
			toggle: false,
		};

		this.refPreviews = createRef();
	}

	render() {
		const { formats } = this.props;
		const { toggle } = this.state;

		return (
			<SettingsRow
				key={1}
				label={__('Preview', 'wds')}
				description={createInterpolateElement(
					__(
						'See how breadcrumbs will appear on your web page. Click “<strong>Show more</strong>” to reveal the preview of breadcrumbs on all page types.',
						'wds'
					),
					{ strong: <strong /> }
				)}
			>
				<div className="sui-border-frame">
					<div
						className="wds-breadcrumb-previews"
						ref={this.refPreviews}
					>
						{this.renderPreviews()}

						{formats.length > 1 && (
							<a
								href="#"
								className="wds-breadcrumb-preview-expander"
								onClick={(e) => this.handleExpand(e)}
							>
								{!!toggle && (
									<>
										<span className="wds-breadcrumb-preview-expander-text">
											{__('Hide more', 'wds')}
										</span>
										<span className="sui-icon sui-icon-chevron-up"></span>
									</>
								)}
								{!toggle && (
									<>
										<span className="wds-breadcrumb-preview-expander-text">
											{__('Show more', 'wds')}
										</span>
										<span className="sui-icon sui-icon-chevron-down"></span>
									</>
								)}
							</a>
						)}
					</div>
				</div>
			</SettingsRow>
		);
	}

	renderPreviews() {
		const { formats } = this.props;

		return (
			<>
				{this.renderPreview(formats[0])}
				<div
					className="wds-breadcrumb-previews-extra"
					style={{ display: 'none' }}
				>
					{formats.slice(1).map((preview, index) => (
						<React.Fragment key={index}>
							{this.renderPreview(preview)}
						</React.Fragment>
					))}
				</div>
			</>
		);
	}

	renderPreview(preview) {
		const { options } = this.props;

		return (
			<div className="wds-breadcrumb-preview">
				<div className="wds-breadcrumb-preview-label">
					<strong>{preview.label}:</strong>
				</div>
				<div className="wds-breadcrumb-preview-snippets">
					{!!options.prefix && !!options.add_prefix && (
						<>
							<span className="prefix">{options.prefix}</span>
						</>
					)}
					{!!options.home_trail && (
						<>
							<strong>
								<a
									href={ConfigValues.get('home_url', 'admin')}
									target="_blank"
									rel="noreferrer"
								>
									{options.home_label || __('Home', ' ')}
								</a>
							</strong>
							<span className="sui-icon">
								{options.custom_sep === ''
									? this.defaultSeperaterCss[
											options.separator
									  ]
									: options.custom_sep}
							</span>
						</>
					)}
					{preview.snippets.map((snippet, ind) => (
						<React.Fragment key={ind}>
							<strong>{snippet}</strong>
							{ind !== preview.snippets.length - 1 && (
								<span className="sui-icon">
									{options.custom_sep === ''
										? this.defaultSeperaterCss[
												options.separator
										  ]
										: options.custom_sep}
								</span>
							)}
							{ind === preview.snippets.length - 1 &&
								options.hide_post_title === false && (
									<span className="sui-icon">
										{options.custom_sep === ''
											? this.defaultSeperaterCss[
													options.separator
											  ]
											: options.custom_sep}
									</span>
								)}
						</React.Fragment>
					))}
					{!options.hide_post_title && (
						<>
							<span>
								{options.labels[preview.type] ||
									preview.placeholder}
							</span>
						</>
					)}
					{!!options.hide_post_title &&
						preview.type !== 'post' &&
						preview.type !== 'page' && (
							<>
								<span>
									{options.labels[preview.type] ||
										preview.placeholder}
								</span>
							</>
						)}
				</div>
			</div>
		);
	}

	handleExpand(e) {
		e.preventDefault();

		this.setState({
			toggle: !this.state.toggle,
		});

		$(this.refPreviews.current)
			.find('.wds-breadcrumb-previews-extra')
			.slideToggle();
	}

	defaultSeperaterCss = {
		dot: '·',
		'dot-l': '•',
		dash: '-',
		'dash-l': '—',
		pipe: '|',
		'forward-slash': '/',
		'back-slash': '\\',
		tilde: '~',
		'greater-than': '>',
		'less-than': '<',
		'caret-right': '›',
		'caret-left': '‹',
		'arrow-right': '→',
		'arrow-left': '←',
	};
}

const mapStateToProps = (state) => ({ ...state.breadcrumbs });

export default connect(mapStateToProps)(Previews);
