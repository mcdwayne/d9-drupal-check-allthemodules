/**
 * @file
 * Render Static and Dynamics Charts.
 */

(function ($, Drupal, drupalSettings) {

  // Line Chart.
  if ($('#chart-line').length) {
    // The following plot uses a number of options to set the title,
    // add axis labels, and shows how to use the canvasAxisLabelRenderer
    // plugin to provide rotated axis labels.
    var plot2 = $.jqplot('chart-line', [[3,7,9,1,4,6,8,2,5]], {
      // Give the plot a title.
      title: 'Plot With Options',
      // You can specify options for all axes on the plot at once with
      // the axesDefaults object.  Here, we're using a canvas renderer
      // to draw the axis label which allows rotated text.
      axesDefaults: {
        labelRenderer: $.jqplot.CanvasAxisLabelRenderer
      },
      // An axes object holds options for all axes.
      // Allowable axes are xaxis, x2axis, yaxis, y2axis, y3axis, ...
      // Up to 9 y axes are supported.
      axes: {
        // Options for each axis are specified in seperate option objects.
        xaxis: {
          label: "X Axis",
          // Turn off "padding".  This will allow data point to lie on the
          // edges of the grid.  Default padding is 1.2 and will keep all
          // points inside the bounds of the grid.
          pad: 0
        },
        yaxis: {
          label: "Y Axis"
        }
      }
    });
  }

  // BAR Chart.
  if ($('#chart-bar').length) {
    $.jqplot.config.enablePlugins = true;
    var s1 = [2, 6, 7, 10];
    var ticks = ['a', 'b', 'c', 'd'];

    plot1 = $.jqplot('chart-bar', [s1], {
        // Only animate if we're not using excanvas (not in IE 7 or IE 8)..
        animate: !$.jqplot.use_excanvas,
        seriesDefaults:{
            renderer:$.jqplot.BarRenderer,
            pointLabels: { show: true }
        },
        axes: {
            xaxis: {
                renderer: $.jqplot.CategoryAxisRenderer,
                ticks: ticks
            }
        },
        highlighter: { show: false }
    });

    $('#chart-bar').bind('jqplotDataClick',
        function (ev, seriesIndex, pointIndex, data) {
            $('#info-bar').html('series: ' + seriesIndex + ', point: ' + pointIndex + ', data: ' + data);
        }
    );
  }

  // Pie Chart.
  if ($('#chart-pie').length) {

    var data = [
      ['Heavy Industry', 12],['Retail', 9], ['Light Industry', 14],
      ['Out of home', 16],['Commuting', 7], ['Orientation', 9]
    ];
    var plot1 = $.jqplot('chart-pie', [data],
      {
        seriesDefaults: {
          // Make this a pie chart.
          renderer: $.jqplot.PieRenderer,
          rendererOptions: {
            // Put data labels on the pie slices.
            // By default, labels show the percentage of the slice.
            showDataLabels: true
          }
        },
        legend: { show:true, location: 'e' }
      }
    );
  }

  // Dynamic Pie Chart.
  if ($('#chart-dynamic').length) {
    // Dynamic values for total node from content type.
    var data = drupalSettings.dynamicPieChart;
    var plot1 = $.jqplot('chart-dynamic', [data],
      {
        seriesDefaults: {
          // Make this a pie chart.
          renderer: $.jqplot.PieRenderer,
          rendererOptions: {
            // Put data labels on the pie slices.
            // By default, labels show the percentage of the slice.
            showDataLabels: true
          }
        },
        legend: { show:true, location: 'e' }
      }
    );
  }

})(jQuery, Drupal, drupalSettings);
