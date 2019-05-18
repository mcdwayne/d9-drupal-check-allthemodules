/**
 * @file
 * Fast Autocomplete scripts.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Enables Fast Autocomplete functionality.
   */
  Drupal.behaviors.fac = {
    attach: function (context, settings) {
      $.each(drupalSettings.fac, function (index, value) {
        $(value.inputSelectors).once('fac').fastAutocomplete(value);
      });
    }
  };

})(jQuery, Drupal);
