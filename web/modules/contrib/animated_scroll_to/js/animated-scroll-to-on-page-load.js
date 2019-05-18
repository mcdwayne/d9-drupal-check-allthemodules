/**
 * @file
 * Attaches the animated scroll to on page load functionality.
 */

(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.animatedScrollToOnPageLoad = {
    attach: function (context, drupalSettings) {

      // Check if the context is the initial page load and if a hash is present in the URL.
      if (context === document && window.location.hash) {

        // Define some standard default settings.
        var delay = 300;
        var speed = 600;
        var pause = 3000;
        var correction = 0;
        var easing = 'swing';

        // Override the standard default settings with the user default settings.
        if (drupalSettings.animated_scroll_to && drupalSettings.animated_scroll_to.default_settings) {
          delay = parseInt(drupalSettings.animated_scroll_to.default_settings.delay);
          speed = parseInt(drupalSettings.animated_scroll_to.default_settings.default_speed);
          pause = parseInt(drupalSettings.animated_scroll_to.default_settings.default_pause);
          correction = parseInt(drupalSettings.animated_scroll_to.default_settings.default_correction);
          easing = drupalSettings.animated_scroll_to.default_settings.default_easing;
        }

        // Make sure the browser starts at the top of the page.
        scroll(0,0);

        // Get the string with hashes from the URL and make an array of it.
        var hashes = window.location.hash.split('#');

        // Removes the first item from the array, because it will always be empty.
        hashes.shift();

        // Adds a default state to the elements.
        hashes.forEach(function (hash, index) {
          // The CSS 'not' selectors are needed because it conflicts with bootstrap data-toggle. See
          // https://www.drupal.org/project/animated_scroll_to/issues/3036743 for more information.
          var selector = '#' + hash + ':not([href="#"]):not([data-toggle])';
          $(selector).attr('data-scroll-state', 'will-become-active');
        });

        // Set the total pause, which will increase for each item.
        var totalPause = delay;

        // Loop through the hashes, to start the animated scroll.
        hashes.forEach(function (hash, index) {

          // Create the element selector from the hash.
          var selector = '#' + hash + ':not([href="#"]):not([data-toggle])';

          // Check if the element exists. If not, we will skip it in the animation.
          if ($(selector).length > 0) {

            // Get the position of the element from the top of the document.
            var elementPosition = $(selector).offset().top;

            // Check if the element has an correction/offset on the top.
            var elementSpeed = ($(selector).data('scroll-speed')) ? $(selector).data('scroll-speed') : speed;
            var elementPause = ($(selector).data('scroll-pause')) ? $(selector).data('scroll-pause') : pause;
            var elementCorrection = ($(selector).data('scroll-correction')) ? $(selector).data('scroll-correction') : correction;
            var elementEasing = ($(selector).data('scroll-easing')) ? $(selector).data('scroll-easing') : easing;

            // Calculating the position to scroll to.
            var scrollToPosition = elementPosition - elementCorrection;

            // Calculating the pause for the current element, skip it for the first item, because of the start delay.
            if (index > 0) {
              totalPause = totalPause + elementSpeed + elementPause;
            }

            // Trigger the scroll on the html/body element.
            // It's wrapped in a setTimeout function for when there are multiple elements.
            setTimeout(function () {
              $('[data-scroll-state="is-active"]').attr('data-scroll-state', 'was-active');
              $(selector).attr('data-scroll-state', 'is-becoming-active');

              $('html, body').stop().animate({
                scrollTop: scrollToPosition + 'px'
              }, elementSpeed, elementEasing, function () {
                $(selector).attr('data-scroll-state', 'is-active');
              });
            }, totalPause);
          }
        });
      }

    }
  };

})(jQuery, Drupal, drupalSettings);
