/**
 * @file
 */

(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.flot_examples = {
    attach: function () {
      var dataset = drupalSettings.flot.placeholder.data;
      var options = drupalSettings.flot.placeholder.options;
      options.yaxis = {
        tickFormatter: function (v) {
          return v + ' cm';
        }
      };
      $.plot($('#placeholder'), dataset, options);
    }
  };
}(jQuery, Drupal, drupalSettings));
