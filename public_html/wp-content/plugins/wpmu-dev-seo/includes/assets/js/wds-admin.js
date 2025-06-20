/* eslint-disable no-var */
(function ($) {
	window.Wds = window.Wds || {};

	window.Wds.version = window._wds_admin.version;

	/**
	 * General scoped variable getter
	 *
	 * @param {string} scope   Scope to check for variable
	 * @param {string} varname Particular varname
	 *
	 * @return {string} Found value or false
	 */
	window.Wds.get =
		window.Wds.get ||
		function (scope, varname) {
			scope = scope || 'general';
			return (window['_wds_' + scope] || {})[varname] || false;
		};

	/**
	 * Fetch localized string for a particular context
	 *
	 * @param {string} scope  Scope to check for strings
	 * @param {string} string Particular string to check for
	 *
	 * @return {string} Localized string
	 */
	window.Wds.l10n =
		window.Wds.l10n ||
		function (scope, string) {
			return (Wds.get(scope, 'strings') || {})[string] || string;
		};

	/**
	 * Fetch template for a particular context
	 *
	 * @param {string} scope    Scope to check for templates
	 * @param {string} template Particular template to check for
	 *
	 * @return {string} Template markup
	 */
	window.Wds.template =
		window.Wds.template ||
		function (scope, template) {
			return (Wds.get(scope, 'templates') || {})[template] || '';
		};

	/**
	 * Compiles the template using underscore templaing facilities
	 *
	 * This is a simple wrapper with templating settings override,
	 * Used because of the PHP ASP tags issues with linters and
	 * deprecated PHP setups.
	 *
	 * @param {string} tpl Template to expand
	 * @param {Object} obj Optional data object
	 *
	 * @return {string} Compiled template
	 */
	window.Wds.tpl_compile = function (tpl, obj) {
		var setup = _.templateSettings,
			value;
		_.templateSettings = {
			evaluate: /\{\{(.+?)}}/g,
			interpolate: /\{\{=(.+?)}}/g,
			escape: /\{\{-(.+?)}}/g,
		};
		value = _.template(tpl, obj);
		_.templateSettings = setup;
		return value;
	};

	window.Wds.qtips = function ($elements) {
		$elements.each(function () {
			var $element = $(this);
			$element.qtip(
				$.extend(
					{
						style: {
							classes: 'wds-qtip qtip-rounded',
						},
						position: {
							my: 'bottom center',
							at: 'top center',
						},
					},
					$element.data()
				)
			);
		});
	};

	window.Wds.hook_toggleables = function () {
		var toggleContent = function () {
			var $content = $(this),
				$formField = $content.closest('.sui-form-field'),
				$toggle = $formField.find('> .sui-toggle'),
				$checkbox = $toggle.find('input[type="checkbox"]'),
				isActive = $checkbox.is(':checked');

			if (isActive) {
				$content.show();
			} else {
				$content.hide();
			}

			$checkbox.on('change', function () {
				toggleContent.apply($content);
			});
		};
		$('.sui-toggle-content').each(toggleContent);
	};

	window.Wds.hook_conditionals = function () {
		var rootSelector = '.wds-conditional';

		function showConditionalElements($select) {
			var $root = $select.closest(rootSelector);

			$.each($root.find('.wds-conditional-inside'), function (index, el) {
				var $conditionalEl = $(el),
					conditionalData = $conditionalEl.data('conditional-val');
				if (!conditionalData) {
					return;
				}

				if (conditionalData.split('|').includes($select.val())) {
					$conditionalEl.show();
				} else {
					$conditionalEl.hide();
				}
			});
		}

		var $selects = $('select', $(rootSelector)).not(
			'.wds-conditional-inside *'
		);

		$.each($selects, function (index, select) {
			showConditionalElements($(select));

			$(select).on('change', function () {
				showConditionalElements($(this));
				return false;
			});
		});
	};

	window.Wds.accordion = function (callback) {
		$(document).on('click', '.wds-accordion-handle', function () {
			var $handle = $(this),
				$section = $handle.closest('.wds-accordion-section');

			if ($section.is('.disabled')) {
				return;
			}

			var $accordion = $handle.closest('.wds-accordion');

			if ($section.is('.open')) {
				$section.removeClass('open');
			} else {
				$accordion.find('.open').removeClass('open');
				$section.addClass('open');
			}

			if (callback) {
				callback();
			}
		});
	};

	window.Wds.link_dropdown = function () {
		function closeAllDropdowns($except) {
			var $dropdowns = $('.wds-links-dropdown');
			if ($except) {
				$dropdowns = $dropdowns.not($except);
			}
			$dropdowns.removeClass('open');
		}

		$('body').on('click', function (e) {
			var $this = $(e.target),
				$el = $this.closest('.wds-links-dropdown');

			if ($el.length === 0) {
				closeAllDropdowns();
			} else if ($this.is('a')) {
				e.preventDefault();
				closeAllDropdowns($el);

				$el.toggleClass('open');
			}
		});
	};

	window.Wds.media_item_selector = function ($root) {
		if (!(wp || {}).media) {
			return;
		}

		var $button = $root.find('.sui-upload-button'),
			$closeButton = $root.find('.sui-upload-file button'),
			$fileName = $root.find('.sui-upload-file > span'),
			$input = $root.find('input[type="hidden"]'),
			$preview = $root.find('.sui-image-preview'),
			idx = $root.attr('id'),
			field = $root.data('field');

		wp.media.frames.wds_media_url = wp.media.frames.wds_media_url || {};
		wp.media.frames.wds_media_url[idx] =
			wp.media.frames.wds_media_url[idx] ||
			new wp.media({
				multiple: false,
				library: { type: 'image' },
			});

		$button.on('click', function (e) {
			if (e && e.preventDefault) e.preventDefault();
			wp.media.frames.wds_media_url[idx].open();

			return false;
		});

		$closeButton.on('click', function (e) {
			if (e && e.preventDefault) e.preventDefault();

			$fileName.html('');
			setValue('', '');
			return false;
		});

		wp.media.frames.wds_media_url[idx].on('select', function () {
			var selection = wp.media.frames.wds_media_url[idx]
					.state()
					.get('selection'),
				mediaItemId = '',
				mediaItemUrl = '',
				mediaItemFilename = '';

			if (!selection) {
				return false;
			}

			selection.each(function (model) {
				mediaItemId = model.get('id');
				mediaItemUrl = model.get('url');
				mediaItemFilename = model.get('filename');
			});

			if (!mediaItemId || !mediaItemUrl) {
				return false;
			}

			$fileName.html(mediaItemFilename);
			setValue(mediaItemId, mediaItemUrl);
			$root.addClass('sui-has_file');
		});

		function setValue(id, url) {
			$preview.css('background-image', 'url("' + url + '")');
			$input.val(field === 'id' ? id : url);
			$root.removeClass('sui-has_file');
		}
	};

	window.Wds.styleable_file_input = function () {
		function fileInput($context) {
			var $root = $context.closest('.sui-upload');

			return {
				file_input: $root.find('input[type="file"]'),
				upload_button: $root.find('.sui-upload-button'),
				file_details: $root.find('.sui-upload-file'),
				file_name: $root.find('.sui-upload-file > span'),
				clear_button: $root.find('.sui-upload-file button'),
			};
		}

		function getFileName(path) {
			if (!path) {
				return '';
			}

			var delimiter = path.includes('\\') ? '\\' : '/';

			return path.split(delimiter).pop();
		}

		function handleClearButtonClick(e) {
			e.preventDefault();

			var el = fileInput($(this));

			el.upload_button.show();
			el.file_details.hide();
			el.file_input.val(null);
		}

		function handleFileInputChange() {
			var el = fileInput($(this)),
				filePath = el.file_input.val();

			if (!filePath) {
				return;
			}

			el.file_details.show();
			el.file_name.html(getFileName(filePath));
			el.upload_button.hide();
		}

		function setDefaultsOnPageLoad() {
			$('.sui-upload').each(function () {
				handleFileInputChange.apply(this);
			});
		}

		$(document)
			.on(
				'click',
				'.sui-upload .sui-upload-file button',
				handleClearButtonClick
			)
			.on(
				'change',
				'.sui-upload input[type="file"]',
				handleFileInputChange
			);

		$(setDefaultsOnPageLoad);
	};

	window.Wds.styleable_checkbox = function ($element) {
		$element.each(function () {
			var $checkbox = $(this);

			if ($checkbox.closest('.wds-checkbox-container').length) {
				return;
			}

			$checkbox.wrap('<div class="wds-checkbox-container">');
			$checkbox.wrap('<label>');
			$checkbox.after('<span></span>');
		});
	};

	window.Wds.dismissible_message = function () {
		function removeMessage(event) {
			event.preventDefault();

			var $dismissLink = $(this),
				$messageBox = $dismissLink.closest(
					'.wds-mascot-message, .wds-notice'
				),
				messageKey = $messageBox.data('key');

			$messageBox.remove();
			if (messageKey) {
				$.post(
					ajaxurl,
					{
						action: 'wds_dismiss_message',
						message: messageKey,
						_wds_nonce: window._wds_admin.nonce,
					},
					'json'
				);
			}
		}

		$(document).on(
			'click',
			'.wds-mascot-bubble-dismiss, .wds-notice-dismiss',
			removeMessage
		);
	};

	window.Wds.vertical_tabs = function () {
		jQuery(document).on('click', '.wds-vertical-tabs a', function () {
			var tab = $(this).data('target'),
				urlParts = location.href.split('&tab=');

			history.replaceState({}, '', urlParts[0] + '&tab=' + tab);
			switchToTab(tab);

			event.preventDefault();
			event.stopPropagation();
			return false;
		});

		function switchToTab(tab) {
			hideAllExceptActive(tab);
			addCurrentClass(tab);
			updateActiveTabInput(tab);

			$('.wds-vertical-tabs').trigger('wds_vertical_tabs:tab_change', [
				$('#' + tab).get(0),
			]);
		}

		function hideAllExceptActive(tab) {
			$('.wds-vertical-tab-section').addClass('hidden');
			$('#' + tab).removeClass('hidden');
		}

		function addCurrentClass(tab) {
			$('.wds-vertical-tabs li').removeClass('current');
			$('[data-target="' + tab + '"]')
				.closest('li')
				.addClass('current');
		}

		function updateActiveTabInput(tab) {
			$('#wds-admin-active-tab').val(tab);
		}
	};

	window.Wds.update_progress_bar = function ($element, value) {
		if (!$element.is('.wds-progress.sui-progress-block') || isNaN(value)) {
			return;
		}

		var rounded = parseFloat(value).toFixed();
		if (rounded > 100) {
			rounded = 100;
		}

		$element.data('progress', value);
		$element.find('.sui-progress-text span').html(rounded + '%');
		$element.find('.sui-progress-bar span').width(value + '%');
	};

	window.Wds.open_dialog = function (
		id,
		focusAfterClosed,
		focusAfterOpen,
		closeOnEsc
	) {
		if (!focusAfterClosed) {
			focusAfterClosed = 'container';
		}

		if (closeOnEsc === 'undefined') {
			closeOnEsc = true;
		}

		SUI.openModal(id, focusAfterClosed, focusAfterOpen, false, closeOnEsc);
	};

	window.Wds.close_dialog = function () {
		SUI.closeModal();
	};

	window.Wds.macro_dropdown = function () {
		jQuery(document).on('change', '.wds-allow-macros select', function () {
			var $select = jQuery(this),
				$input = $select
					.closest('.wds-allow-macros')
					.find('input, textarea'),
				macro = $select.val();

			$input
				.val($input.val().trim() + ' ' + macro)
				.trigger('change')
				.trigger('input');
		});
	};

	window.Wds.conditional_fields = function () {
		function elValue($el) {
			if ($el.is(':checkbox')) {
				return $el.prop('checked') ? '1' : '0';
			}
			return $el.val();
		}

		function handleConditional($child) {
			var parent = $child.data('parent'),
				$parent = $('#' + parent),
				parentVal = $child.attr('data-parent-val'),
				values = [];

			if (parentVal.indexOf(',') !== -1) {
				values = parentVal.split(',');
			} else {
				values.push(parentVal);
			}

			if (values.indexOf(elValue($parent)) === -1) {
				$child.hide();
			} else {
				$child.show();
			}
		}

		$('.wds-conditional-child').each(function () {
			handleConditional($(this));
		});

		$('.wds-conditional-parent').on('change', function () {
			var $parent = $(this),
				parentId = $parent.attr('id'),
				$children = $('[data-parent="' + parentId + '"]');

			$children.each(function () {
				handleConditional($(this));
			});
		});
	};

	window.Wds.floating_message = function () {
		jQuery('.wds-floating-notice-trigger').trigger('click');
	};

	window.Wds.show_floating_message = function (id, message, type) {
		if (!type) {
			type = 'info';
		}

		SUI.openNotice(id, '<p>' + message + '</p>', {
			type,
			autoclose: {
				show: true,
				timeout: 5000,
			},
		});
	};

	/**
	 * Gets cookie value.
	 * Source: https://www.quirksmode.org/js/cookies.html
	 *
	 * @param {string} name Cookie key to get.
	 *
	 * @return {string}|{Null} Value.
	 */
	window.Wds.get_cookie = function (name) {
		var nameEQ = name + '=';
		var ca = document.cookie.split(';');
		for (var i = 0; i < ca.length; i++) {
			var c = ca[i];
			while (c.charAt(0) === ' ') c = c.substring(1, c.length);
			if (c.indexOf(nameEQ) === 0)
				return c.substring(nameEQ.length, c.length);
		}
		return null;
	};

	/**
	 * Sets cookie value.
	 * Source: https://www.quirksmode.org/js/cookies.html
	 *
	 * @param {string} name  Cookie key to set.
	 * @param {string} value Value to set.
	 * @param {number} days  Cookie expiration time.
	 */
	window.Wds.set_cookie = function (name, value, days) {
		var expires = '';
		if (days) {
			var date = new Date();
			date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
			expires = '; expires=' + date.toUTCString();
		}
		document.cookie = name + '=' + (value || '') + expires + '; path=/';
	};

	/**
	 * Expires a cookie
	 * Source: https://www.quirksmode.org/js/cookies.html
	 *
	 * @param {string} name Cookie key to expire.
	 */
	window.Wds.delete_cookie = function (name) {
		document.cookie = name + '=; Max-Age=-99999999;';
	};

	window.Wds.inverted_toggle = function () {
		var selector = '.wds-inverted-toggle';
		$(selector).each(function (index, inverted) {
			$('.sui-toggle [type="checkbox"]', $(inverted)).on(
				'change',
				function () {
					var $checkbox = $(this),
						$hidden = $checkbox
							.closest(selector)
							.find('.wds-inverted-toggle-value');

					$hidden.prop(
						'value',
						$checkbox.is(':checked') ? '' : $hidden.data('value')
					);
				}
			);
		});
	};

	function init() {
		window.Wds.floating_message();
		window.Wds.inverted_toggle();

		manageNewFeatureStatus();
	}

	$(init);

	window.Wds.reporting_schedule = function () {
		function changeFrequency() {
			var $radio = $(this),
				frequency = $radio.val(),
				$dowSelects = $('.wds-dow').hide();

			$dowSelects.find('select').prop('disabled', true);
			$dowSelects.filter('.' + frequency).show();
			$dowSelects
				.filter('.' + frequency)
				.find('select')
				.prop('disabled', false);
		}

		$(document).on(
			'change',
			'.wds-frequency-tabs .sui-tab-item > input[type="radio"]',
			changeFrequency
		);
		$(
			'.wds-frequency-tabs .sui-tab-item > input[type="radio"]:checked'
		).each(function () {
			changeFrequency.apply(this);
		});
	};

	function manageNewFeatureStatus() {
		$(document).ready(function () {
			if (
				$('.toplevel_page_wds_wizard .wp-menu-name').has(
					'.wds-new-feature-status'
				).length > 0
			) {
				$.ajax({
					url: window.ajaxurl,
					method: 'POST',
					data: {
						action: 'smartcrawl_new_feature_status',
						_wds_nonce: window._wds_admin.nonce,
						step: 0,
					},
				});
			}
		});
	}
})(jQuery);
