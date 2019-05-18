/**
 * @file
 * Attaches behaviors for the dialect module.
 */

(function ($, Drupal, document) {

  'use strict';

  /**
   * Attaches the dialect behavior.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attach dialect functionality on the content pages.
   */
  Drupal.behaviors.dialectBehavior = {

    attach: function (context) {

      function toggleTooltip() {
        // @todo check accessibility
        // @todo check for transition out
        var dropdown = $(this).next('.dialect__tooltip');
        var linkWidth = $(this).outerWidth();
        var linkLeft = $(this).position().left + linkWidth / 2;

        var dropdownHeight = dropdown.outerHeight();
        var documentHeight = $(document).height();

        /* toggle display: none to allow transition and avoid
         window overflow */
        dropdown.toggleClass('js-active');
        setTimeout(function () {
          if (dropdown.hasClass('js-active')) {
            dropdown.addClass('js-visible')
          } else {
            dropdown.removeClass('js-visible')
          }
        }, 150);

        var dropdownWidth = dropdown.outerWidth();
        var dropdownOffset = dropdown.offset();
        var linkOffset = $(this).offset();
        /* Assumes there is no window overflow-x */
        var windowWidth = $(window).width();

        /* Avoid dropdown overflow-x from browser window */
        if (linkOffset.left < dropdownWidth / 2) {
          dropdown.css('left', dropdownWidth / 2);
          dropdown.find('.tooltip-arrow').css('left', '10px');
        } else if (linkOffset.left + linkWidth / 2 + dropdownWidth / 2 > windowWidth) {
          dropdown.css('right', -dropdownWidth / 2 + "px");
          dropdown.find('.tooltip-arrow').css('left', '90%');
        } else {
          dropdown.css('left', linkLeft + "px");
        }

        /* Avoid dropdown overflow-y from browser window */
        if (documentHeight < dropdownHeight + linkOffset.top) {
          dropdown.css('top', -dropdownHeight - 10 + "px");
          dropdown.find('.tooltip-arrow').css('top', '100%');
        }

        /* Hide previous toggled dropdown */
        $('.dialect__tooltip').not(dropdown).removeClass('js-visible js-active');

        return false;
      }

      $(context).find('.dialect__current-language').on('click touch', toggleTooltip);

      $(document).on('click touch', function () {
        $(context).find('.dialect__tooltip').removeClass('js-visible js-active');
      });

    }
  }
})(jQuery, Drupal, document);
