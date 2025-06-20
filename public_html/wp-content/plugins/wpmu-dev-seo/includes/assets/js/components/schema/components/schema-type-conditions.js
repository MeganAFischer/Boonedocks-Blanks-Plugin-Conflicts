import React from 'react';
import { __ } from '@wordpress/i18n';
import { first } from 'lodash-es';
import SchemaTypeCondition from './schema-type-condition';
import Button from '../../button';

export default class SchemaTypeConditions extends React.Component {
	static defaultProps = {
		conditions: [],
		onAddGroup: () => false,
		onChange: () => false,
		onAdd: () => false,
		onDelete: () => false,
	};

	render() {
		const groups = this.getConditionGroupElements();
		const { onAddGroup } = this.props;

		return (
			<div className="wds-schema-type-rules">
				<span className="sui-icon-link" aria-hidden="true" />
				<small>
					<strong>{__('Location', 'wds')}</strong>
				</small>
				<span className="sui-description">
					{__(
						'Create a set of rules to determine where this schema.org type will be enabled or excluded.',
						'wds'
					)}
				</span>

				{groups}

				<Button
					text={__('Add Rule (Or)', 'wds')}
					ghost={true}
					onClick={() => onAddGroup()}
					icon="sui-icon-plus"
				/>
			</div>
		);
	}

	getConditionGroupElements() {
		const { conditions } = this.props;

		return conditions.map((conditionGroup, conditionGroupIndex) => {
			const firstCondition = first(conditionGroup);
			return (
				<div
					key={'condition-group-' + firstCondition.id}
					className="wds-schema-type-condition-group"
				>
					{conditionGroupIndex === 0 && (
						<span>{__('Rule', 'wds')}</span>
					)}
					{conditionGroupIndex !== 0 && (
						<span>{__('Or', 'wds')}</span>
					)}

					{this.getConditionElements(
						conditionGroup,
						conditionGroupIndex
					)}
				</div>
			);
		});
	}

	getConditionElements(conditionGroup, conditionGroupIndex) {
		const { onChange, onAdd, onDelete } = this.props;

		return conditionGroup.map((condition, conditionIndex) => (
			<SchemaTypeCondition
				onChange={(id, lhs, operator, rhs) =>
					onChange(id, lhs, operator, rhs)
				}
				onAdd={(id) => onAdd(id)}
				onDelete={(id) => onDelete(id)}
				disableDelete={
					conditionGroupIndex === 0 && conditionIndex === 0
				}
				key={condition.id}
				id={condition.id}
				lhs={condition.lhs}
				operator={condition.operator}
				rhs={condition.rhs}
			/>
		));
	}
}
