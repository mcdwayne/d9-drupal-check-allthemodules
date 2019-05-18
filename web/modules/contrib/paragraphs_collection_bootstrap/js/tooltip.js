/**
 * @file
 * Attaches behavior for bootstrap tooltip.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Initializes tooltip.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.pcb_tooltip = {
    attach: function (context, settings) {
      $('[data-toggle="tooltip"]').tooltip();
    }
  };

})(jQuery, Drupal, drupalSettings);