/* globals _wds_term_form, _wds_onpage */
/* eslint-disable no-var */
// noinspection ES6ConvertVarToLetConst

(function ($) {
	function refreshPreview() {
		var $previewContainer = $('.wds-preview-container');

		if (!$previewContainer.length) {
			return;
		}

		$previewContainer.addClass('wds-preview-loading');

		$.post(
			ajaxurl,
			{
				action: 'wds-term-form-preview',
				wds_title: $('#wds_title').val(),
				wds_description: $('#wds_metadesc').val(),
				term_id: $('[name="tag_ID"]').val(),
				// eslint-disable-next-line camelcase
				_wds_nonce: _wds_term_form.nonce,
			},
			'json'
		)
			.done(function (data) {
				if ((data || {}).success) {
					$('.wds-metabox-preview').replaceWith(
						$((data || {}).markup)
					);
				}
			})
			.always(function () {
				$('.wds-preview-container').removeClass('wds-preview-loading');
			});
	}

	function getReplacementFunction(randomTerm) {
		return function (value) {
			return Wds.macroReplacement.replace_term_macros(value, randomTerm);
		};
	}

	function hookPreviewAndIndicators() {
		var $title = $('#wds_title'),
			$description = $('#wds_metadesc');

		var params = new URLSearchParams(location.search);
		var type = params.get('taxonomy');
		var randomItem = (Wds.randomTerms || {})[type];

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
		}
	}

	function init() {
		Wds.hook_toggleables();

		$(document)
			.on(
				'input propertychange',
				'.wds-meta-field',
				_.debounce(refreshPreview, 1000)
			)
			.on('click', '.wds-edit-meta .sui-button', function () {
				$(this)
					.closest('.wds-edit-meta')
					.find('.sui-border-frame')
					.toggle();
			});

		$(refreshPreview);

		hookPreviewAndIndicators();
	}

	$(init);
})(jQuery);
