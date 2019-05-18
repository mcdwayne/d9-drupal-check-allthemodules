/**
 * @file
 */

(function ($) {
  'use strict';
  Drupal.behaviors.flot_examples = {
    attach: function () {
      var options = drupalSettings.flot.placeholder.options;
      var data = drupalSettings.flot.placeholder.data;
      var placeholder = $('#placeholder');
      placeholder.bind('plotselected', function (event, ranges) {
        $('#selection').text(ranges.xaxis.from.toFixed(1) + ' to ' + ranges.xaxis.to.toFixed(1));
        var zoom = $('#zoom').prop('checked');
        if (zoom) {
          $.each(plot.getXAxes(), function (_, axis) {
            var opts = axis.options;
            opts.min = ranges.xaxis.from;
            opts.max = ranges.xaxis.to;
          });
          plot.setupGrid();
          plot.draw();
          plot.clearSelection();
        }
      });
      placeholder.bind('plotunselected', function (event) {
        $('#selection').text('');
      });
      var plot = $.plot(placeholder, data, options);
      $('#clearSelection').click(function () {
        plot.clearSelection();
      });
      $('#setSelection').click(function () {
        plot.setSelection({
          xaxis: {
            from: 1994,
            to: 1995
          }
        });
      });
    }
  };
}(jQuery));
