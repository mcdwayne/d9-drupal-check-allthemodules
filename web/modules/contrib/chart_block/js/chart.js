/**
 * @file
 * Contains the javascript for drawing the charts.
 */

(function ($) {

  'use strict';

  $(document).ready(function () {

    var chart = drupalSettings.chart_block;

    for (var key in chart) {

      if (chart[key]['chart_type'] == 'line') {
        $.jqplot(chart[key]['chart_div_id'], [chart[key]['chart_data']], {
          title: chart[key]['chart_title'],
          axesDefaults: {
            labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
          },
          seriesDefaults: {
            rendererOptions: {
              smooth: true,
            }
          },
          axes: {
            xaxis: {
              renderer: $.jqplot.CategoryAxisRenderer,
              label: chart[key]['chart_x_axis_label'],
              pad: 0,
            },
            yaxis: {
              label: chart[key]['chart_y_axis_label']
            }
          },
          highlighter: {
            show: true,
            tooltipLocation: 'n',
            tooltipAxes: 'y',
            showMarker: false,
            useAxesFormatters: false
          },
        });
      }
      else if (chart[key]['chart_type'] == 'bar') {
        $('#' + chart[key]['chart_div_id']).jqplot([chart[key]['chart_data']], {
          title: chart[key]['chart_title'],
          seriesDefaults: {
            renderer:$.jqplot.BarRenderer,
            rendererOptions: {
              varyBarColor: true,
            },
          },
          axesDefaults: {
            labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
          },
          axes: {
            xaxis: {
              label: chart[key]['chart_x_axis_label'],
              renderer: $.jqplot.CategoryAxisRenderer,
            },
            yaxis: {
              label: chart[key]['chart_y_axis_label']
            }
          },
          highlighter: {
            show: true,
            tooltipLocation: 'n',
            tooltipAxes: 'y',
            showMarker: false,
            useAxesFormatters: false
          },
        });
      }
      else if (chart[key]['chart_type'] == 'pie') {
        $.jqplot(chart[key]['chart_div_id'], [chart[key]['chart_data']], {
          title: chart[key]['chart_title'],
          seriesDefaults: {
            renderer: jQuery.jqplot.PieRenderer,
            rendererOptions: {
              showDataLabels: true,
              padding: 15,
            }
          },
          legend: {
            show: true,
            location: 'e',
          },
          highlighter: {
            show: true,
            tooltipAxes: 'y',
            showMarker: false,
            useAxesFormatters: false
          },
        });
      }

    }

  });

})(jQuery);
