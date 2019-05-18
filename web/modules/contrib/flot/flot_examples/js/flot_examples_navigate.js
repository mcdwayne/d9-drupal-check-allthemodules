/**
 * @file
 */

(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.flot_examples = {
    attach: function () {
      // Generate data set from a parametric function with a fractal look.
      var options = drupalSettings.flot.placeholder.options;

      function sumf(f, t, m) {
        var res = 0;
        for (var i = 1; i < m; ++i) {
          res += f(i * i * t) / (i * i);
        }
        return res;
      }
      var d1 = [];
      for (var t = 0; t <= 2 * Math.PI; t += 0.01) {
        d1.push([sumf(Math.cos, t, 10), sumf(Math.sin, t, 10)]);
      }
      var data = [d1];
      var placeholder = $('#placeholder');
      var plot = $.plot(placeholder, data, options);
      // Show pan/zoom messages to illustrate events.
      placeholder.bind('plotpan', function (event, plot) {
        var axes = plot.getAxes();
        $('.message').html('Panning to x: ' + axes.xaxis.min.toFixed(2)
          + ' &ndash; ' + axes.xaxis.max.toFixed(2)
          + ' and y: ' + axes.yaxis.min.toFixed(2)
          + ' &ndash; ' + axes.yaxis.max.toFixed(2));
      });
      placeholder.bind('plotzoom', function (event, plot) {
        var axes = plot.getAxes();
        $('.message').html('Zooming to x: ' + axes.xaxis.min.toFixed(2)
          + ' &ndash; ' + axes.xaxis.max.toFixed(2)
          + ' and y: ' + axes.yaxis.min.toFixed(2)
          + ' &ndash; ' + axes.yaxis.max.toFixed(2));
      });
      // Add zoom out button.
      $("<div class='button' style='right:20px;top:20px'>zoom out</div>")
        .appendTo(placeholder)
        .click(function (event) {
          event.preventDefault();
          plot.zoomOut();
        });
      // And add panning buttons
      // little helper for taking the repetitive work out of placing
      // panning arrows.
      function addArrow(dir, right, top, offset) {
        var base = '/modules/flot/flot_examples/images/';
        $("<img class='button' src='" + base + 'arrow-' + dir + "'.gif' style='right:" + right + 'px;top:' + top + "px'>")
          .appendTo(placeholder)
          .click(function (e) {
            e.preventDefault();
            plot.pan(offset);
          });
      }
      addArrow('left', 55, 60, {left: -100});
      addArrow('right', 25, 60, {left: 100});
      addArrow('up', 40, 45, {top: -100});
      addArrow('down', 40, 75, {top: 100});
    }
  };
}(jQuery, Drupal, drupalSettings));
