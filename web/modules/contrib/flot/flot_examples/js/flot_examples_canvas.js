/**
 * @file
 */

(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.flot_examples = {
    attach: function () {
      var data = drupalSettings.flot.placeholder.data;
      var options = drupalSettings.flot.placeholder.options;

      $.plot('#placeholder', data, options);
      $('input').change(function () {
        options.canvas = $(this).is(':checked');
        $.plot('#placeholder', data, options);
      });
    }
  };
}(jQuery, Drupal, drupalSettings));
