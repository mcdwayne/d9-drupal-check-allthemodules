/**
 * @file
 */

(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.flot_examples = {
    attach: function () {
      var options_p = drupalSettings.flot.placeholder.options;
      var options_o = drupalSettings.flot.overview.options;
      var data = drupalSettings.flot.placeholder.data;
      // Helper for returning the weekends in a period.
      function weekendAreas(axes) {
        var markings = [];
        var d = new Date(axes.xaxis.min);
        // Go to the first Saturday.
        d.setUTCDate(d.getUTCDate() - ((d.getUTCDay() + 1) % 7));
        d.setUTCSeconds(0);
        d.setUTCMinutes(0);
        d.setUTCHours(0);
        var i = d.getTime();
        // When we don't set yaxis, the rectangle automatically
        // extends to infinity upwards and downwards.
        do {
          markings.push({xaxis: {from: i, to: i + 2 * 24 * 60 * 60 * 1000}});
          i += 7 * 24 * 60 * 60 * 1000;
        } while (i < axes.xaxis.max);
        return markings;
      }
      options_p['grid'] = {markings: weekendAreas};
      var plot = $.plot('#placeholder', data, options_p);
      var overview = $.plot('#overview', data, options_o);
      // Now connect the two.
      $('#placeholder').bind('plotselected', function (event, ranges) {
        // Do the zooming.
        $.each(plot.getXAxes(), function (_, axis) {
          var opts = axis.options;
          opts.min = ranges.xaxis.from;
          opts.max = ranges.xaxis.to;
        });
        plot.setupGrid();
        plot.draw();
        plot.clearSelection();
        // don't fire event on the overview to prevent eternal loop.
        overview.setSelection(ranges, true);
      });
      $('#overview').bind('plotselected', function (event, ranges) {
        plot.setSelection(ranges);
      });
    }
  };
}(jQuery, Drupal, drupalSettings));
