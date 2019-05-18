/**
 * @file
 */

(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.flot_examples = {
    attach: function () {
      var data = drupalSettings.flot.placeholder.data;
      var options = drupalSettings.flot.placeholder.options;
      var plot = $.plot('#placeholder', data, options);
      var legends = $('#placeholder .legendLabel');
      legends.each(function () {
        // Fix the widths so they don't jump around.
        $(this).css('width', $(this).width());
      });
      var updateLegendTimeout = null;
      var latestPosition = null;

      function updateLegend() {
        updateLegendTimeout = null;
        var pos = latestPosition;
        var axes = plot.getAxes();
        if (pos.x < axes.xaxis.min || pos.x > axes.xaxis.max ||
          pos.y < axes.yaxis.min || pos.y > axes.yaxis.max) {
          return;
        }
        var i;
        var j;
        var dataset = plot.getData();
        for (i = 0; i < dataset.length; ++i) {
          var series = dataset[i];
          // Find the nearest points, x-wise.
          for (j = 0; j < series.data.length; ++j) {
            if (series.data[j][0] > pos.x) {
              break;
            }
          }

          // Now Interpolate.
          var y;
          var p1 = series.data[j - 1];
          var p2 = series.data[j];

          if (p1 == null) {
            y = p2[1];
          }
          else if (p2 == null) {
            y = p1[1];
          }
          else {
            y = p1[1] + (p2[1] - p1[1]) * (pos.x - p1[0]) / (p2[0] - p1[0]);
          }

          legends.eq(i).text(series.label.replace(/=.*/, '= ' + y.toFixed(2)));
        }
      }

      $('#placeholder').bind('plothover', function (event, pos, item) {
        latestPosition = pos;
        if (!updateLegendTimeout) {
          updateLegendTimeout = setTimeout(updateLegend, 50);
        }
      });

    }
  };
}(jQuery, Drupal, drupalSettings));
