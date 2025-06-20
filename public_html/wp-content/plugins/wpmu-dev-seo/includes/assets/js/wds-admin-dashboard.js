/* globals _wds_dashboard, _wds_admin */

(function ($) {
	window.Wds = window.Wds || {};

	function reloadBox(boxId) {
		return $.post(
			ajaxurl,
			{
				action: 'wds-reload-box',
				box_id: boxId,
				// eslint-disable-next-line camelcase
				_wds_nonce: _wds_dashboard.nonce,
			},
			function (data) {
				if ((data || {}).success) {
					if (!Array.isArray(boxId)) {
						boxId = [boxId];
					}

					$.each(boxId, function (index, value) {
						const $box = $('#' + value);

						if ($box.length && data[value]) {
							$box.replaceWith(data[value]);
						}
					});
				}
			},
			'json'
		).always(function () {
			updatePageStatus();
			loadAccordions();
			loadScoreCircles();
		});
	}

	function activateComponent(e) {
		e.preventDefault();
		const $button = $(this),
			$box = $button.closest('.wds-dashboard-widget'),
			boxId = $box.attr('id');

		beforeAjaxRequest($button);

		$.post(
			ajaxurl,
			{
				action: 'wds-activate-component',
				option: $button.data('optionId'),
				flag: $button.data('flag'),
				value: $button.get(0).hasAttribute('data-value')
					? $button.data('value')
					: 1,
				// eslint-disable-next-line camelcase
				_wds_nonce: _wds_dashboard.nonce,
			},
			function (data) {
				if ((data || {}).success) {
					reloadBoxAndDependents(boxId);
				}
			},
			'json'
		);
	}

	function activateModule(e) {
		e.preventDefault();
		const $button = $(this),
			$box = $button.closest('.wds-dashboard-widget'),
			boxId = $box.attr('id');

		beforeAjaxRequest($button);

		$.post(
			ajaxurl,
			{
				action: `smartcrawl_activate_${$button.data('module')}`,
				// eslint-disable-next-line camelcase
				_wds_nonce: _wds_dashboard.nonce,
			},
			function (data) {
				if ((data || {}).success) {
					reloadBoxAndDependents(boxId);
				}
			},
			'json'
		);
	}

	function activateSubmodule(e) {
		e.preventDefault();
		const $button = $(this),
			$box = $button.closest('.wds-dashboard-widget'),
			boxId = $box.attr('id');

		beforeAjaxRequest($button);

		$.post(
			ajaxurl,
			{
				action: `smartcrawl_update_options_${$button.data(
					'submodule'
				)}`,
				active: true,
				// eslint-disable-next-line camelcase
				_wds_nonce: _wds_admin.nonce,
			},
			function (data) {
				if ((data || {}).success) {
					reloadBoxAndDependents(boxId);
				}
			},
			'json'
		);
	}

	function boxExists(boxId) {
		return !!$('#' + boxId).length;
	}

	function reloadBoxAndDependents(boxId) {
		const $box = $('#' + boxId),
			dependent = $box.data('dependent');

		let boxIds = [boxId];

		if (dependent) {
			boxIds = boxIds.concat(dependent.split(';'));
		}

		return reloadBox(_.filter(boxIds, boxExists));
	}

	function updatePageStatus() {
		$('.wds-disabled-during-request').prop('disabled', false);
		$('.sui-button-onload').removeClass('sui-button-onload');
	}

	function beforeAjaxRequest($targetElement) {
		if (!$targetElement.is('.sui-button-onload')) {
			$targetElement.addClass('sui-button-onload');
			$('.wds-disabled-during-request').prop('disabled', true);
		}
	}

	function reloadBoxes() {
		const $boxesRequiringRefresh = $('.wds-box-refresh-required'),
			boxIds = [];

		if ($boxesRequiringRefresh.length) {
			$.each($boxesRequiringRefresh, function () {
				const $box = $(this).closest('.wds-dashboard-widget'),
					boxId = $box.attr('id');

				if (!boxIds.includes(boxId)) {
					boxIds.push(boxId);
				}
			});

			reloadBox(boxIds);
		}

		setTimeout(reloadBoxes, 20000);
	}

	function loadAccordions() {
		$('.wds-page .sui-accordion').each(function () {
			SUI.suiAccordion(this);
		});
	}

	function loadScoreCircles() {
		$('.sui-circle-score:not(.loaded)').each(function () {
			SUI.loadCircleScore(this);
		});
	}

	function lighthouseAccordionItemClick(event) {
		event.preventDefault();
		event.stopPropagation();

		const healthUrl =
			// eslint-disable-next-line camelcase
			_wds_dashboard.health_page_url +
			'&device=' +
			// eslint-disable-next-line camelcase
			_wds_dashboard.lighthouse_widget_device;
		redirectToCheck(event, healthUrl);
	}

	function redirectToCheck(event, healthPage) {
		const item = $(event.target).closest('.sui-accordion-item');

		if (item.length && healthPage) {
			window.location.href = healthPage + '&check=' + item.attr('id');
		}
	}

	function hookLighthouseAccordionItemClick() {
		$('#wds-lighthouse div.sui-accordion-item-header')
			.off('click')
			.on('click', lighthouseAccordionItemClick);
	}

	function startNewLighthouseTest() {
		const $button = $(this);
		$button.addClass('wds-run-test-onload');

		return $.post(
			ajaxurl,
			{
				action: 'wds-lighthouse-start-test',
				// eslint-disable-next-line camelcase
				_wds_nonce: _wds_dashboard.lighthouse_nonce,
			},
			function (data) {
				if (data.success) {
					// eslint-disable-next-line camelcase
					window.location.href = _wds_dashboard.health_page_url;
				} else {
					$button.removeClass('wds-run-test-onload');
				}
			},
			'json'
		);
	}

	function init() {
		reloadBoxes();
		loadAccordions();

		window.Wds.accordion();
		window.Wds.dismissible_message();

		$(document)
			.on('click', '.wds-lighthouse-start-test', startNewLighthouseTest)
			.on('click', '.wds-activate-component', activateComponent)
			.on('click', '.wds-activate-module', activateModule)
			.on('click', '.wds-activate-submodule', activateSubmodule);

		hookLighthouseAccordionItemClick();
	}

	$(init);
})(jQuery);
