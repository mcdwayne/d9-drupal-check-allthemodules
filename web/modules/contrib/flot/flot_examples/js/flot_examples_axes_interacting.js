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
      $.each(plot.getAxes(), function (i, axis) {
        if (!axis.show) {
          return;
        }

        var box = axis.box;

        $("<div class='axisTarget' style='position:absolute; left:" + box.left + 'px; top:' + box.top + 'px; width:' + box.width + 'px; height:' + box.height + "px'></div>")
          .data('axis.direction', axis.direction)
          .data('axis.n', axis.n)
          .css({backgroundColor: '#f00', opacity: 0, cursor: 'pointer'})
          .appendTo(plot.getPlaceholder())
          .hover(
          function () {
            $(this).css({opacity: 0.10});
          },
          function () {
            $(this).css({opacity: 0});
          }
        )
          .click(function () {
            $('#click').text('You clicked the ' + axis.direction + axis.n + 'axis!');
          });
      });
    }
  };
}(jQuery, Drupal, drupalSettings));
