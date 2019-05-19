/**
 * @file
 * Attaches behaviors for the Selectize.js module.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behavior to the page from defined settings for selectize to each specified element.
   */
  Drupal.behaviors.selectize = {
    attach: function (context) {
      if (typeof drupalSettings.selectize != 'undefined') {
        $.each(drupalSettings.selectize, function (index, value) {
          $('#' + index).selectize(JSON.parse(value));
        });
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
