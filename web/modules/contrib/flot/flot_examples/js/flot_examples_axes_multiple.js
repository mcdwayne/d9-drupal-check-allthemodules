/**
 * @file
 */

(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.flot_examples = {
    attach: function () {
      var data = drupalSettings.flot.placeholder.data;
      var options = drupalSettings.flot.placeholder.options;
      function euroFormatter(v, axis) {
        return v.toFixed(axis.tickDecimals) + '€';
      }

      function doPlot(position) {
        options.yaxes = [{min: 1}, {
          // Align if we are to the right.
          alignTicksWithAxis: position === 'right' ? 1 : null,
          position: position,
          tickFormatter: euroFormatter
        }];

        $.plot('#placeholder', data, options);
      }

      doPlot('right');

      $('button').click(function () {
        doPlot($(this).text());
      });
    }
  };
}(jQuery, Drupal, drupalSettings));
