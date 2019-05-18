/**
 * @file
 */

(function ($) {
  'use strict';
  Drupal.behaviors.flot_examples = {
    attach: function () {
      $("<div id='tooltip'></div>").css({
        'position': 'absolute',
        'display': 'none',
        'border': '1px solid #fdd',
        'padding': '2px',
        'background-color': '#fee',
        'opacity': 0.80
      }).appendTo('body');
      $('#placeholder').bind('plothover', function (event, pos, item) {

        if ($('#enablePosition:checked').length > 0) {
          var str = '(' + pos.x.toFixed(2) + ', ' + pos.y.toFixed(2) + ')';
          $('#hoverdata').text(str);
        }

        if ($('#enableTooltip:checked').length > 0) {
          if (item) {
            var x = item.datapoint[0].toFixed(2);
            var y = item.datapoint[1].toFixed(2);

            $('#tooltip').html(item.series.label + ' of ' + x + ' = ' + y)
              .css({top: item.pageY + 5, left: item.pageX + 5})
              .fadeIn(200);
          }
          else {
            $('#tooltip').hide();
          }
        }
      });
      $('#placeholder').bind('plotclick', function (event, pos, item) {
        if (item) {
          $('#clickdata').text(' - click point ' + item.dataIndex + ' in ' + item.series.label);
          plot.highlight(item.series, item.datapoint);
        }
      });
    }
  };
}(jQuery));
