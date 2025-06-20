import React from 'react';
import Dropdown from '../../../components/dropdown';
import DropdownButton from '../../../components/dropdown-button';
import { __, sprintf } from '@wordpress/i18n';
import Checkbox from '../../../components/checkbox';
import classnames from 'classnames';
import GeoUtil from '../../../utils/geo-util';
import { getRedirectTypes } from '../../../utils/redirect-utils';

export default class RedirectItem extends React.Component {
	static defaultProps = {
		title: '',
		source: '',
		destination: '',
		permalink: '',
		type: '',
		rules: [],
		options: [],
		selected: false,
		onToggle: () => false,
		onEdit: () => false,
		onDelete: () => false,
	};

	render() {
		const {
			selected,
			title,
			source,
			destination,
			rules,
			onToggle,
			onEdit,
			onDelete,
		} = this.props;

		return (
			<div
				className={classnames('wds-redirect-item sui-builder-field', {
					'wds-redirect-has-title': !!title,
				})}
			>
				<div className="wds-redirect-item-checkbox">
					<Checkbox
						checked={selected}
						onChange={(isChecked) => onToggle(isChecked)}
					/>
				</div>

				<div className="wds-redirect-item-source">
					<div className="sui-tooltip" data-tooltip={source}>
						<div className="wds-redirect-item-source-trimmed">
							{source}
						</div>
					</div>
					{title && <small>{title}</small>}
				</div>

				<div className="wds-redirect-item-destination">
					<small>
						{destination
							? destination.title || destination.url
							: rules?.length
							? __('Location-based Redirection', 'wds')
							: ''}
					</small>
				</div>

				<div className="wds-redirect-item-options">
					{this.renderType()}
					{this.renderOptions()}
					{this.renderRules()}
				</div>

				<div className="wds-redirect-item-dropdown">
					<Dropdown
						buttons={[
							<DropdownButton
								key={0}
								className="wds-edit-redirect-item"
								icon="sui-icon-pencil"
								text={__('Edit', 'wds')}
								onClick={() => onEdit()}
							/>,
							<DropdownButton
								key={1}
								className="wds-remove-redirect-item"
								icon="sui-icon-trash"
								text={__('Remove', 'wds')}
								red={true}
								onClick={() => onDelete()}
							/>,
						]}
					/>
				</div>
			</div>
		);
	}

	renderType() {
		const types = getRedirectTypes();

		const { type } = this.props;

		return (
			<>
				<span
					className="sui-tooltip sui-tooltip-constrained"
					data-tooltip={types[type]}
					style={{ '--tooltip-width': '170px' }}
				>
					<span className="sui-tag sui-tag-sm">{type}</span>
				</span>
			</>
		);
	}

	renderOptions() {
		const { options } = this.props;

		const labels = {
			regex: __('Regex', 'wds'),
		};

		return options.map(
			(option) =>
				labels.hasOwnProperty(option) && (
					<span
						className="sui-tag sui-tag-yellow sui-tag-sm"
						key={option}
					>
						{labels[option]}
					</span>
				)
		);
	}

	renderRules() {
		const { rules } = this.props;

		if (!rules?.length) {
			return '';
		}

		let froms = [],
			notFroms = [];

		rules.forEach((rule) => {
			if (rule.indicate === '1') {
				notFroms = notFroms.concat(rule.countries);
			} else {
				froms = froms.concat(rule.countries);
			}
		});

		let content = '';

		froms = froms
			.filter((fr, ind) => froms.indexOf(fr) === ind)
			.map((fr) => GeoUtil.getCountries()[fr])
			.sort();

		if (froms.length) {
			if (froms.length > 3) {
				content = sprintf(
					// translators: %s: comma separated country names.
					__('From %s, etc.', 'wds'),
					froms.slice(0, 3).join(', ')
				);
			} else {
				content = sprintf(
					// translators: %s: comma separated country names.
					__('From %s.', 'wds'),
					froms.join(', ')
				);
			}
		}

		notFroms = notFroms
			.filter((nf, ind) => notFroms.indexOf(nf) === ind)
			.map((nf) => GeoUtil.getCountries()[nf])
			.sort();

		if (notFroms.length) {
			if (content.length) {
				content += '\n';
			}

			if (notFroms.length > 3) {
				content += sprintf(
					// translators: %s: comma separated country names.
					__('Not from %s, etc.', 'wds'),
					notFroms.slice(0, 3).join(', ')
				);
			} else {
				content += sprintf(
					// translators: %s: comma separated country names.
					__('Not from %s.', 'wds'),
					notFroms.join(', ')
				);
			}
		}
		return (
			<span
				className="sui-tooltip sui-tooltip-constrained"
				data-tooltip={content}
				style={{ '--tooltip-width': '170px' }}
			>
				<span className="sui-icon-web-globe-world" aria-hidden="true" />
			</span>
		);
	}
}
