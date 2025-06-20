/* globals _wds_onpage */

/* eslint-disable no-var */

(function ($) {
	function toggleArchiveStatus() {
		var $checkbox = $(this),
			$accordionSection = $checkbox.closest('.sui-accordion-item'),
			disabledClass = 'sui-accordion-item--disabled',
			openClass = 'sui-accordion-item--open';

		if (!$checkbox.is(':checked')) {
			$accordionSection.removeClass(openClass).addClass(disabledClass);
		} else {
			$accordionSection.removeClass(disabledClass);
		}
	}

	function saveStaticHomeSettings() {
		var $button = $(this),
			formData = $(':input', '#tab_static_homepage').serialize(),
			params = addQueryParams(formData, {
				action: 'wds-onpage-save-static-home',
				// eslint-disable-next-line camelcase
				_wds_nonce: _wds_onpage.nonce,
			});

		$button.addClass('sui-button-onload');
		$.post(ajaxurl, params, 'json').done(function () {
			$button.removeClass('sui-button-onload');
			window.location.href = addQueryParams(window.location.href, {
				'settings-updated': 'true',
			});
		});
	}

	function addQueryParams(base, params) {
		return base + '&' + $.param(params);
	}

	function initOnpage() {
		$(document).on(
			'click',
			'.wds-save-static-home-settings',
			saveStaticHomeSettings
		);

		$(
			'[name="wds_onpage_options[custom_title_min_length]"], [name="wds_onpage_options[custom_title_max_length]"]'
		).on('change keyup keypress', function (el) {
			handleCharLengthChange(el.target, 'title');
		});

		$(
			'[name="wds_onpage_options[custom_metadesc_min_length]"], [name="wds_onpage_options[custom_metadesc_max_length]"]'
		).on('change keyup keypress', function (el) {
			handleCharLengthChange(el.target, 'metadesc');
		});

		// Also update on init, because of potential hash change
		window.Wds.macro_dropdown();
		window.Wds.vertical_tabs();

		var $tabStatusCheckboxes = $(
			'.sui-accordion-item-header input[type="checkbox"]'
		);
		$tabStatusCheckboxes.each(function () {
			toggleArchiveStatus.apply($(this));
		});
		$tabStatusCheckboxes.on('change', toggleArchiveStatus);
	}

	function handleCharLengthChange(el, type) {
		var $minLen = $(
			'[name="wds_onpage_options[custom_' + type + '_min_length]"]'
		);
		var minLenVal = parseInt($minLen.val());
		var $maxLen = $(
			'[name="wds_onpage_options[custom_' + type + '_max_length]"]'
		);
		var maxLenVal = parseInt($maxLen.val());
		var $submitBtn = $('#tab_settings button[type="submit"]');

		$minLen.closest('.sui-form-field').removeClass('sui-form-field-error');
		$minLen.siblings('.wds-char-length-empty').css('display', 'none');
		$minLen.siblings('.wds-char-length-invalid').css('display', 'none');
		$maxLen.closest('.sui-form-field').removeClass('sui-form-field-error');
		$maxLen.siblings('.wds-char-length-empty').css('display', 'none');
		$maxLen.siblings('.wds-char-length-invalid').css('display', 'none');

		$submitBtn.attr('disabled', false);

		if (!minLenVal) {
			$minLen.closest('.sui-form-field').addClass('sui-form-field-error');
			$minLen.siblings('.wds-char-length-empty').css('display', 'block');
			$submitBtn.attr('disabled', true);
		}

		if (!maxLenVal) {
			$maxLen.closest('.sui-form-field').addClass('sui-form-field-error');
			$maxLen.siblings('.wds-char-length-empty').css('display', 'block');
			$submitBtn.attr('disabled', true);
		}

		if (minLenVal && maxLenVal && minLenVal >= maxLenVal) {
			var $el = $(el);
			$el.closest('.sui-form-field').addClass('sui-form-field-error');
			$el.siblings('.wds-char-length-invalid').css('display', 'block');
			$submitBtn.attr('disabled', true);
		}
	}

	function handleAccordionItemClick() {
		var $accordionItem = $(this).closest('.sui-accordion-item');

		// Keep one section open at a time
		$('.sui-accordion-item--open')
			.not($accordionItem)
			.removeClass('sui-accordion-item--open');
	}

	function updateSitemapWarning() {
		var $checkbox = $(this);
		var $notice = $checkbox
			.closest('.wds-toggle')
			.find('.sui-description .sui-notice');

		if (!$notice.length) {
			return;
		}

		$notice.toggleClass('hidden', $checkbox.is(':checked'));
	}

	function hookPreviewAndIndicators() {
		var hasStaticHomepage = (Wds.randomPosts || {})['static-home'] || false;
		if (hasStaticHomepage) {
			hookPreviewAndIndicatorsForTab(
				'tab_static_homepage',
				function (staticHomepage) {
					return function (value) {
						return Wds.macroReplacement.replace(
							value,
							staticHomepage
						);
					};
				},
				Wds.randomPosts
			);
		} else {
			var homeUrl = Wds.get('onpage', 'home_url');
			hookPreviewAndIndicatorsForTab(
				'tab_homepage',
				function () {
					return function (value) {
						return Wds.macroReplacement.do_replace(value, {});
					};
				},
				{ home: { url: homeUrl } }
			);
		}
		hookPreviewAndIndicatorsForTab(
			'tab_post_types',
			function (randomPost) {
				return function (value) {
					return Wds.macroReplacement.replace(value, randomPost);
				};
			},
			Wds.randomPosts
		);
		hookPreviewAndIndicatorsForTab(
			'tab_taxonomies',
			function (randomTerm) {
				return function (value) {
					return Wds.macroReplacement.replace_term_macros(
						value,
						randomTerm
					);
				};
			},
			Wds.randomTerms
		);
		hookPreviewAndIndicatorsForTab(
			'tab_archives',
			function (randomItem) {
				return function (value) {
					return Wds.macroReplacement.do_replace(
						value,
						{},
						randomItem.replacements
					);
				};
			},
			Wds.get('onpage', 'random_archives')
		);
		hookPreviewAndIndicatorsForTab(
			'tab_buddypress',
			function (randomItem) {
				return function (value) {
					return Wds.macroReplacement.do_replace(
						value,
						{},
						randomItem.replacements
					);
				};
			},
			Wds.get('onpage', 'random_buddypress')
		);
	}

	function hookPreviewAndIndicatorsForTab(
		tabId,
		getReplacementFunction,
		randomItems
	) {
		$('#' + tabId + ' input[type="text"][id^="title-"]').each(function (
			index,
			input
		) {
			var $title = $(input),
				id = $title.attr('id'),
				$container = $title.closest('[data-type]'),
				$description = $('[id^="metadesc-"]', $container),
				type = id.replace('title-', ''),
				randomItem = (randomItems || {})[type];

			if (randomItem) {
				var replaceMacros = getReplacementFunction(randomItem, type),
					titleIndicator = new Wds.OptimumLengthIndicator(
						$title,
						replaceMacros,
						{
							// eslint-disable-next-line camelcase
							lower: parseInt(_wds_onpage.title_min, 10),
							// eslint-disable-next-line camelcase
							upper: parseInt(_wds_onpage.title_max, 10),
							default_value: $title.attr('placeholder'),
						}
					),
					descIndicator = new Wds.OptimumLengthIndicator(
						$description,
						replaceMacros,
						{
							// eslint-disable-next-line camelcase
							lower: parseInt(_wds_onpage.metadesc_min, 10),
							// eslint-disable-next-line camelcase
							upper: parseInt(_wds_onpage.metadesc_max, 10),
							default_value: $description.attr('placeholder'),
						}
					);

				titleIndicator.update_indicator();
				descIndicator.update_indicator();

				var updatePreview = getUpdatePreviewFunction(
					replaceMacros,
					getRandomItemUrl(randomItem)
				);

				updatePreview.apply($title);
				updatePreview.apply($description);

				$title.on('input propertychange', updatePreview);
				$description.on('input propertychange', updatePreview);
			}
		});
	}

	function getRandomItemUrl(item) {
		if (item.url) {
			return item.url;
		}

		if (item.get_permalink) {
			return item.get_permalink();
		}

		return '';
	}

	function getUpdatePreviewFunction(replaceMacros, url) {
		return function () {
			var $container = $(this).closest('[data-type]'),
				$title = $container.find('[id^="title-"]'),
				$description = $container.find('[id^="metadesc-"]'),
				template = Wds.tpl_compile(Wds.template('onpage', 'preview')),
				titleMaxLength = Wds.get('onpage', 'title_max_length'),
				metadescMaxLength = Wds.get('onpage', 'metadesc_max_length'),
				promises = [];

			var titleValue = $title.val();
			if (!titleValue) {
				titleValue = $title.attr('placeholder');
			}
			promises.push(replaceMacros(titleValue));

			var descValue = $description.val();
			if (!descValue) {
				descValue = $description.attr('placeholder');
			}
			promises.push(replaceMacros(descValue));

			Promise.all(promises).then(function (values) {
				var markup = template({
					link: url,
					title: Wds.String_Utils.process_string(
						values[0],
						titleMaxLength
					),
					description: Wds.String_Utils.process_string(
						values[1],
						metadescMaxLength
					),
				});

				$container.find('.wds-preview-container').replaceWith(markup);
			});
		};
	}

	function deactivateSitemapModule() {
		$(this).addClass('sui-button-onload');
		return $.post(
			ajaxurl,
			{
				action: 'wds-deactivate-onpage-module',
				_wds_nonce: _wds_onpage.nonce,
			},
			() => {
				window.location.reload();
			},
			'json'
		);
	}

	function init() {
		initOnpage();
		hookPreviewAndIndicators();
		$('.sui-accordion-item-header')
			.off('click.sui.accordion')
			.on('click.sui.accordion', handleAccordionItemClick);
		Wds.hook_conditionals();
		$('#wds-deactivate-onpage-component').on(
			'click',
			deactivateSitemapModule
		);
		$('[value^="meta_robots-noindex-"]').on('change', updateSitemapWarning);
	}

	// Boot
	$(init);
})(jQuery);
