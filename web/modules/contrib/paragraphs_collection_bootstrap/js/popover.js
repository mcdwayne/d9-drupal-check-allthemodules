/**
 * @file
 * Attaches behavior fot bootstrap popover.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Initializes popover.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.pcb_popover = {
    attach: function (context, settings) {
      $('[data-toggle="popover"]').popover();
    }
  };

})(jQuery, Drupal, drupalSettings);