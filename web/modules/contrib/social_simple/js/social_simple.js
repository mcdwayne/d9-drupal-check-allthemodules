/**
 * @file
 * Provides the functionality to the social links buttons.
 */

(function ($) {
  'use strict';
  Drupal.behaviors.social_simple = {
    attach: function (context, settings) {
      $('.social-buttons-links a', context).once('social-buttons-link').each(function () {
        if ($(window).width() > 500) {
          $(this, context).on('click', function (e) {
            var h = $(this).data("popup-height"),
                w = $(this).data("popup-width"),
                open_popup = typeof $(this).data('popup-open') !== "undefined" ? $(this).data('popup-open') : true;

            if (open_popup === false) {
              // If the popup is not necessary no need to continue.
              return;
            }

            e.preventDefault();
            var dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : screen.left;
            var dualScreenTop = window.screenTop != undefined ? window.screenTop : screen.top;

            var width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
            var height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;

            var left = ((width / 2) - (w / 2)) + dualScreenLeft;
            var top = ((height / 2) - (h / 2)) + dualScreenTop;

            window.open(
                $(this).attr("href"),
                "share",
                "top=" + top + ",left=" + left + ",width=" + w + ",height=" + h
            );
          });
        }
      });
    }
  }

}(jQuery));
