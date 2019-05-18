/**
 * @file
 * Attaches the animated scroll to in page functionality.
 */

(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.animatedScrollToInPage = {
    attach: function (context, drupalSettings) {

      // Check for the context and if there are any links which have in-page anchors. The CSS 'not' selectors are needed
      // because it conflicts with bootstrap data-toggle. See https://www.drupal.org/project/animated_scroll_to/issues/3036743
      // for more information.
      var elementsSelector = 'a[href^="#"]:not([href="#"]):not([data-toggle])';

      if (context === document && $(elementsSelector).length > 0) {

        // Define some standard default settings.
        var speed = 600;
        var correction = 0;
        var easing = 'swing';

        // Override the standard default settings with the user default settings.
        if (drupalSettings.animated_scroll_to && drupalSettings.animated_scroll_to.default_settings) {
          speed = parseInt(drupalSettings.animated_scroll_to.default_settings.default_speed);
          correction = parseInt(drupalSettings.animated_scroll_to.default_settings.default_correction);
          easing = drupalSettings.animated_scroll_to.default_settings.default_easing;
        }

        // Loop through each link with an in-page anchor.
        $(elementsSelector).each(function (index, link) {

          // Attach an touch/click event on the link.
          $(link).on('touch click', function (event) {

            // Prevent the browser of executing the default behaviour of the link.
            event.preventDefault();

            // Define the selector as a variable for later use.
            var selector = $(link).attr('href');

            // Check if the destination (element) exists on the page.
            if ($(selector).length > 0) {

              // Get the position of the element from the top of the document.
              var elementPosition = $(selector).offset().top;

              // Get the position of the current scroll.
              var currentScrollPosition = $(document).scrollTop();

              // Check if the element has an correction/offset on the top.
              var elementSpeed = ($(selector).data('scroll-speed')) ? $(selector).data('scroll-speed') : speed;
              var elementCorrection = ($(selector).data('scroll-correction')) ? $(selector).data('scroll-correction') : correction;
              var elementEasing = ($(selector).data('scroll-easing')) ? $(selector).data('scroll-easing') : easing;

              // Calculating the position to scroll to.
              var pixelsToScrollFromCurrentPosition = elementPosition - currentScrollPosition;
              var scrollToPosition = (currentScrollPosition + pixelsToScrollFromCurrentPosition) - elementCorrection;

              // Trigger the scroll on the html/body element.
              $('html, body').stop().animate({
                scrollTop: scrollToPosition + 'px'
              }, elementSpeed, elementEasing);
            }
          });
        });
      }

    }
  };

})(jQuery, Drupal, drupalSettings);
