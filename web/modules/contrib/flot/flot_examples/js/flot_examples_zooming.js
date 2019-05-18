/**
 * @file
 */

(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.flot_examples = {
    attach: function () {
      // Setup plot.
      var placeholder_options = drupalSettings.flot.placeholder.options;
      var overview_options = drupalSettings.flot.overview.options;
      function getData(x1, x2) {
        var d = [];
        for (var i = 0; i <= 100; ++i) {
          var x = x1 + i * (x2 - x1) / 100;
          d.push([x, Math.sin(x * Math.sin(x))]);
        }
        return [
          {label: 'sin(x sin(x))', data: d}
        ];
      }
      var startData = getData(0, 3 * Math.PI);
      var plot = $.plot('#placeholder', startData, placeholder_options);
      // Create the overview plot.
      var overview = $.plot('#overview', startData, overview_options);
      // Now connect the two.
      $('#placeholder').bind('plotselected', function (event, ranges) {
        // Clamp the zooming to prevent eternal zoom.
        if (ranges.xaxis.to - ranges.xaxis.from < 0.00001) {
          ranges.xaxis.to = ranges.xaxis.from + 0.00001;
        }
        if (ranges.yaxis.to - ranges.yaxis.from < 0.00001) {
          ranges.yaxis.to = ranges.yaxis.from + 0.00001;
        }
        // Do the zooming.
        plot = $.plot('#placeholder', getData(ranges.xaxis.from, ranges.xaxis.to),
          $.extend(true, {}, placeholder_options, {
            xaxis: {min: ranges.xaxis.from, max: ranges.xaxis.to},
            yaxis: {min: ranges.yaxis.from, max: ranges.yaxis.to}
          })
          );
          // don't fire event on the overview to prevent eternal loop.
        overview.setSelection(ranges, true);
      });
      $('#overview').bind('plotselected', function (event, ranges) {
        plot.setSelection(ranges);
      });
    }
  };
}(jQuery, Drupal, drupalSettings));
