/**
 * @file
 */

(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.flot_examples = {
    attach: function () {
      var data = drupalSettings.flot.placeholder.data;
      function plotWithOptions(t) {
        data[0]['threshold']['below'] = t;
        $.plot('#placeholder', data);
      }

      $('.controls input').click(function (e) {
        e.preventDefault();
        var t = parseFloat(this.id.replace('T', ''));
        plotWithOptions(t);
      });
    }
  };
}(jQuery, Drupal, drupalSettings));
