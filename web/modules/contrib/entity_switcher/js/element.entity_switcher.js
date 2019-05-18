/**
 * @file
 * Entity Reference Switcher basic functionality.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Attach the Entity Reference Switcher function to select.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.entityReferenceSwitcher = {
    attach: function (context) {
      // Toggle the classes off/on to get the right colors on the buttons.
      $('.entity-switcher-container input', context).on('change', function () {
        $(this).next().toggleClass('switcher-status-off').toggleClass('switcher-status-on');

        $(this).closest('.entity-switcher-wrapper')
          .find('div[class^="switcher-entity-"]')
          .toggle();
      });

      // Check current state to show/hide the correct form (to prevent bad
      // behavior with browsers back button).
      $('.entity-switcher-container', context).each(function () {
        if ($(this).find('input:checked').length) {
          $(this).find('.switcher-slider').addClass('switcher-status-on').removeClass('switcher-status-off');
          $(this).closest('.entity-switcher-wrapper')
            .find('.switcher-entity-off').hide().end()
            .find('.switcher-entity-on').show();
        }
        else if ($(this).find('input:not(:checked)').length) {
          $(this).find('.switcher-slider').addClass('switcher-status-off').removeClass('switcher-status-on');
          $(this).closest('.entity-switcher-wrapper')
            .find('.switcher-entity-off').show().end()
            .find('.switcher-entity-on').hide();
        }
      });
    }
  };

})(jQuery, Drupal);
