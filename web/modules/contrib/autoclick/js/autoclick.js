/**
 * @file
 * Defines Javascript behaviors for the auto click module.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Behaviors for auto click module.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches auto click behavior for all pages.
   */
  Drupal.behaviors.autoClick = {
    attach: function (context) {
      $('[data-autoclick-timer]', context).each(
        function() {
          var $element = $(this);
          var element_timer = parseInt($element.attr('data-autoclick-timer')) * 1000;

          setTimeout(
            function () {
              if ($element.is('a')) {
                window.location.href = $element.attr('href');
              }
              else {
                $element.trigger('click');
              }
            },
            element_timer
          );
        }
      );
    }
  };

})(jQuery, Drupal);
