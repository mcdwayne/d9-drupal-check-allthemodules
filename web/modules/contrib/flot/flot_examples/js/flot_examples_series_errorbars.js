/**
 * @file
 */

(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.flot_examples = {
    attach: function () {
      function drawArrow(ctx, x, y, radius) {
        ctx.beginPath();
        ctx.moveTo(x + radius, y + radius);
        ctx.lineTo(x, y);
        ctx.lineTo(x - radius, y + radius);
        ctx.stroke();
      }

      function drawSemiCircle(ctx, x, y, radius) {
        ctx.beginPath();
        ctx.arc(x, y, radius, 0, Math.PI, false);
        ctx.moveTo(x - radius, y);
        ctx.lineTo(x + radius, y);
        ctx.stroke();
      }
      var datasets = drupalSettings.flot.placeholder.data;
      var options = drupalSettings.flot.placeholder.options;
      var i = 0;
      $.each(datasets, function (key, val) {
        if (typeof val.points !== 'undefined') {
          if (val.points.yerr.upperCap === 'drawArrow') {
            datasets[i].points.yerr.upperCap = drawArrow;
          }
          if (val.points.yerr.lowerCap === 'drawSemiCircle') {
            datasets[i].points.yerr.lowerCap = drawSemiCircle;
          }
        }
        i++;
      });
      $.plot('#placeholder', datasets, options);
    }
  };
}(jQuery, Drupal, drupalSettings));
