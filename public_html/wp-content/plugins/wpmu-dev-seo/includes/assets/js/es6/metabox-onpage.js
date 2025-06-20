import $ from 'jQuery';
import ConfigValues from './config-values';
import { EventTarget } from 'event-target-shim';
import { GutenbergPrimaryTerm } from './primary-term';
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

class MetaboxOnpage extends EventTarget {
	constructor() {
		super();

		this.init_primary_term(this);
	}

	init_primary_term() {
		if (ConfigValues.get_bool('primary_terms_active', 'metabox')) {
			if (ConfigValues.get_bool('gutenberg_active', 'metabox')) {
				GutenbergPrimaryTerm();
			} else {
				this.classicPrimaryTerm(this);
			}
		}
	}

	post(action, data) {
		data = $.extend(
			{
				action: action,
				_wds_nonce: ConfigValues.get('nonce', 'metabox'),
			},
			data
		);

		return $.post(ajaxurl, data);
	}

	classicPrimaryTerm(obj) {
		const taxonomies = _wds_primary.taxonomies_js;
		const wpcPrimaryTermInput = wp.template('wds-select-primary-term');
		let primaryID;
		$(Object.values(taxonomies)).each(function (index, taxonomy) {
			primaryID = taxonomy.primary;
			const taxonomyMetabox = $(`#taxonomy-${taxonomy.name}`);
			const primaryTermInputHtml = wpcPrimaryTermInput({
				taxonomy,
			});
			const categories = $(taxonomyMetabox).find(
				`#${taxonomy.name}checklist li`
			);
			$(categories).each(function (index, element) {
				const id = $(element).find('input[type="checkbox"]').val();
				const showPrimaryLabel = `<span class="primary_controller">
					<span class="wds-primary-show-label">${__('Primary', 'wds')}</span>
					<button type="button" class="wds-make-primary-term hidden" aria-label="Make Primary">${__(
						'Make Primary',
						'wds'
					)}</button>
					</span>`;
				const showMakePrimaryLabel = `<span class="primary_controller">
					<span class="wds-primary-show-label hidden"> ${__('Primary', 'wds')} </span>
					<button type="button" class="wds-make-primary-term" aria-label="Make Primary">${__(
						'Make Primary',
						'wds'
					)}</button>
					</span>`;
				if (
					$(element)
						.find('input[type="checkbox"]')
						.slice(0, 1)
						.prop('checked')
				) {
					const makeButtonHtml =
						Number(id) === Number(primaryID)
							? showPrimaryLabel
							: showMakePrimaryLabel;
					$(makeButtonHtml).insertAfter(
						$(element).find('label').slice(0, 1).find('input')
					);
				} else {
					$(showMakePrimaryLabel).insertAfter(
						$(element).find('label').slice(0, 1).find('input')
					);
					$(element).find('.primary_controller').hide();
				}
				$(element)
					.find('button')
					.slice(0, 1)
					.on('click', function () {
						obj.primaryBtnOnClick(
							this,
							id,
							primaryID,
							taxonomyMetabox,
							taxonomy.name
						);
						primaryID = id;
					});
				$(element)
					.find('input[type="checkbox"]')
					.slice(0, 1)
					.click(function () {
						if (!$(this).prop('checked')) {
							if (Number(id) === Number(primaryID)) {
								primaryID = 0;
								const CheckedCategories = $(
									`#${taxonomy.name}checklist`
								).find('input[type="checkbox"]');
								let firstElement;
								$(CheckedCategories).each(function (index, el) {
									if ($(el).prop('checked')) {
										firstElement = $(el).next();
										primaryID = $(this).val();
										return false;
									}
								});
								$(firstElement)
									.find('button')
									.addClass('hidden');
								$(firstElement)
									.find('span')
									.removeClass('hidden');
								$(`#wds_primary_term_${taxonomy.name}`).val(
									primaryID
								);

								$(this)
									.next()
									.find('button')
									.removeClass('hidden');
								$(this).next().find('span').addClass('hidden');
							}
							$(this).next().hide();
						} else {
							$(this).next().show();
						}
					});
			});
			taxonomyMetabox.append(primaryTermInputHtml);
		});
	}
	primaryBtnOnClick(obj, id, primaryID, taxonomyMetabox, taxonomy) {
		$(`#wds_primary_term_${taxonomy}`).val(id);
		$(obj).parent().find('span').removeClass('hidden');
		$(obj).addClass('hidden');
		$(taxonomyMetabox)
			.find(`#${taxonomy}checklist li input`)
			.each(function () {
				if (Number($(this).val()) === Number(primaryID)) {
					$(this).next().find('span').addClass('hidden');
					$(this).next().find('button').removeClass('hidden');
					return false;
				}
			});
	}
}

export default MetaboxOnpage;
