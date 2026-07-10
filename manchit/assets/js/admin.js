/**
 * Manchit admin panel scripts.
 *
 * @package Manchit
 */
(function ($) {
	'use strict';

	$(function () {
		// Ads repeater.
		var $wrap = $('#manchit-ad-units');
		var tpl = $('#manchit-ad-template').html();

		function reindex() {
			$wrap.children('.manchit-ad-unit').each(function (i) {
				$(this).find('[name]').each(function () {
					var name = $(this).attr('name');
					if (name) {
						$(this).attr('name', name.replace(/units\]\[(?:\d+|__INDEX__)\]/, 'units][' + i + ']'));
					}
				});
			});
		}

		function toggleParagraph($unit) {
			var loc = $unit.find('.manchit-ad-location').val();
			$unit.find('.manchit-ad-paragraph').toggle(loc === 'in_content');
		}

		function toggleType($unit) {
			var type = $unit.find('.manchit-ad-type').val();
			$unit.find('.manchit-when-code').toggle(type !== 'adsense');
			$unit.find('.manchit-when-adsense').toggle(type === 'adsense');
		}

		$('#manchit-add-ad').on('click', function () {
			var index = $wrap.children('.manchit-ad-unit').length;
			var html = tpl.replace(/__INDEX__/g, index);
			var $node = $(html);
			$wrap.append($node);
			toggleParagraph($node);
			toggleType($node);
			reindex();
		});

		$wrap.on('click', '.manchit-remove-ad', function () {
			$(this).closest('.manchit-ad-unit').remove();
			reindex();
		});

		$wrap.on('change', '.manchit-ad-location', function () {
			toggleParagraph($(this).closest('.manchit-ad-unit'));
		});
		$wrap.on('change', '.manchit-ad-type', function () {
			toggleType($(this).closest('.manchit-ad-unit'));
		});

		$wrap.children('.manchit-ad-unit').each(function () {
			toggleParagraph($(this));
			toggleType($(this));
		});

		// Native color inputs (fallback to type=color if wp-color-picker absent).
		$('[data-manchit-color]').each(function () {
			var $input = $(this);
			if ($.fn.wpColorPicker) {
				$input.wpColorPicker();
			} else {
				$input.attr('type', 'color');
			}
		});

		// Media uploader for image fields.
		$('[data-manchit-media]').each(function () {
			var $input = $(this);
			var $btn = $('<button type="button" class="button manchit-media-btn">' + (window.wp && wp.i18n ? wp.i18n.__('اختيار صورة', 'manchit') : 'اختيار صورة') + '</button>');
			$input.after($btn);
			$btn.on('click', function (e) {
				e.preventDefault();
				if (!window.wp || !wp.media) { return; }
				var frame = wp.media({ multiple: false, library: { type: 'image' } });
				frame.on('select', function () {
					var att = frame.state().get('selection').first().toJSON();
					$input.val(att.url);
				});
				frame.open();
			});
		});
	});
})(jQuery);
