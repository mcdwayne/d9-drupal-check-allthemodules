/**
 * @file
 * Sharerich.
 */

(function ($, Drupal, drupalSettings) {

	'use strict';

	$(document).ready(function () {

		// Reset button.
		$('.sharerich-form .button.reset').click(function () {
			var parent = $(this).parent();
			var grand_parent = parent.parent();
			var markup = parent.find('.markup');
			var default_markup = grand_parent.find('.default-markup');
			markup.val(default_markup.val());
		});

		// Sticky.
		$(window).scroll(function () {
			var target = $('.sharerich-wrapper.sharerich-vertical.sharerich-sticky');
			// Find the parent container.
			var container = target.parent();
			if (container.length) {
				if ($(window).scrollTop() > container.offset().top)
					target.addClass('stick');
				else
					target.removeClass('stick');
			}
		});

		// Workaround for allowed protocols limitation.
		var button = $('.sharerich-buttons .rrssb-print a');
		if (button.length) {
			var _href = button.attr('href');
			button.attr('href', (_href.substring(0, 6) == 'window' ? "javascript:" + _href : _href));
		}

		var button = $('.sharerich-buttons .rrssb-whatsapp a');
		if (button.length) {
			var _href = button.attr('href');
			button.attr('href', (_href.substring(0, 6) == '//send' ? "whatsapp:" + _href : _href));
		}

	})

})(jQuery, Drupal, drupalSettings);
