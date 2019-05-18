/**
 * @file
 * Implements the behaviors for the Chart Suite.
 *
 * @ingroup chart_suite
 */
(function($, Drupal, drupalSettiings) {
  'use strict';

  /*--------------------------------------------------------------------
   *
   * Google Charts
   *
   * This script uses the Google Charts service, hosted by Google. The
   * service and its API provides a set of simple charting styles, including
   * line and area plots, bar and pie charts, etc.
   *
   * Use of Google Charts requires these steps:
   *  1. Load the packages needed.
   *  2. Create a chart object attached to a DOM element.
   *  3. Draw the chart, passing in the data and options.
   *
   * For inline JS, like all the Google Charts examples on line, we
   * could execute the above three steps within a <script>.
   *
   * But when used with Drupal, code must be split into steps:
   *  1. Load this JS library (Drupal puts it at the bottom of a page).
   *      a. Load the Google Charts packages.
   *      b. Set up an on-load callback.
   *  2. Use inline JS to mark a DOM element to be turned into a chart.
   *  3. On load:
   *      a. Scan through the DOM finding marked elements.
   *      b. Initialize those elements to build charts.
   *
   * Many of the visualizations add a pull-down menu to select
   * among chart styles. This menu issues an onchange event:
   *  4. On change:
   *      a. Get the menu selection.
   *      b. Re-initialize the right element to rebuild a chart.
   *
   * A complication is that both Google Charts and Drupal have an idea
   * of how to handle onload tasks. Unfortunately, Google Charts
   * sets up an onload that runs *after* Drupal's onload. Since
   * Google Charts finishes loading its packages on its onload, it
   * means Drupal's onload runs too early. We cannot use it to do
   * the sweep for charts and initialize them or Google Charts won't
   * have finished loading yet. We have to use Google Charts' own
   * onload mechanism instead of Drupal's.
   *
   *--------------------------------------------------------------------*/

  // On script load, load Google Charts packages.
  // - corechart = most of the chart types
  // - orgchart  = the org chart type
  // - table     = the HTML table type
  google.charts.load(
    'current',
    {'packages':['corechart','table','orgchart','gauge']});

  // On script load, attach behaviors.
  // - Look for marked DOM elements. For each, get a function from
  //   a property and execute it. The function initializes the chart.
  //   Then unmark the DOM element.
  google.charts.setOnLoadCallback(function() {
    $('.chart_suite_pending').each(function() {
      var id = this.id;
      var f = this.chart_suite_pending;
      f();
      $(this).toggleClass('chart_suite_pending', false);
    });
  });

  /*--------------------------------------------------------------------
   *
   * Module items.
   *
   *--------------------------------------------------------------------*/
  Drupal.chart_suite = Drupal.chart_suite || {
    /*--------------------------------------------------------------------
     *
     * Setup.
     *
     *--------------------------------------------------------------------*/

    /**
     * Builds a chart to display a table with numbers in the 1st column.
     *
     * @param id
     *   The DOM element ID.
     * @param data
     *   The data to render.
     */
    buildTableChartDiv: function(id, data) {
      var nRows = data.data.getNumberOfRows();
      var options = [];

      if (nRows > 1) {
        options.push(new Option('Area (stacked)', 'AreaStacked'));
        options.push(new Option('Area (not stacked)', 'AreaNotStacked'));
        options.push(new Option('Area (stepped)', 'AreaStepped'));
      }

      options.push(new Option('Bars', 'Bar'));
      options.push(new Option('Columns', 'Column'));

      if (nRows > 1) {
        options.push(new Option('Lines (straight)', 'LinesStraight'));
        options.push(new Option('Lines (curved)', 'LinesCurved'));
        options.push(new Option('Scatter', 'Scatter'));
      }

      options.push(new Option('Table', 'Table'));

      Drupal.chart_suite.createChartArea(id, data, options);
    },

    /**
     * Builds a chart to display a table with strings in the 1st column.
     *
     * @param id
     *   The DOM element ID.
     * @param data
     *   The data to render.
     */
    buildStrTableChartDiv: function(id, data) {
      var nRows = data.data.getNumberOfRows();
      var nColumns = data.data.getNumberOfColumns();
      var options = [];

      if (nRows > 1) {
        options.push(new Option('Area (stacked)', 'AreaStacked'));
        options.push(new Option('Area (not stacked)', 'AreaNotStacked'));
        options.push(new Option('Area (stepped)', 'AreaStepped'));
      }

      options.push(new Option('Bars', 'Bar'));
      options.push(new Option('Columns', 'Column'));

      if (nColumns == 2)
      {
        options.push(new Option('Donut', 'Donut'));
        options.push(new Option('Gauges', 'Gauge'));
      }

      if (nRows > 1) {
        options.push(new Option('Lines (straight)', 'LinesStraight'));
        options.push(new Option('Lines (curved)', 'LinesCurved'));
      }

      if ( nColumns == 2 ) {
        options.push(new Option('Pie', 'Pie'));
      }

      if (nRows > 1) {
        options.push(new Option('Scatter', 'Scatter'));
      }

      options.push(new Option('Table', 'Table'));

      Drupal.chart_suite.createChartArea(id, data, options);
    },

    /**
     * Builds a chart to display a tree.
     *
     * @param id
     *   The DOM element ID.
     * @param data
     *   The data to render.
     */
    buildTreeChartDiv: function(id, data) {
      var options = [new Option('Organization chart', 'Org')];
      Drupal.chart_suite.createChartArea(id, data, options);
    },

    /**
     * Create the chart area and menu.
     *
     * @param id
     *   The DOM element ID.
     * @param data
     *   The data to render.
     * @param options
     *   An array of Option objects for a menu of chart types.
     */
    createChartArea: function(id, data, options) {
      // Create a <select> menu with the given options.
      var $menu = $('<select>');
      $menu.append(options);

      // Create a <div> to hold the chart and menu.
      var $menuDiv = $('<div>');
      $menuDiv.addClass('chart_suite-file-select-wrapper');
      $menuDiv.append($menu);

      var $chartDiv = $('<div>');
      var chartId = id + 'chart';
      $chartDiv.attr('id', chartId);
      $chartDiv.addClass('chart_suite-file-chart-wrapper');

      var $parentDiv = $('#' + id);
      $parentDiv.append($menuDiv);
      $parentDiv.append($chartDiv);

      // Set up behaviors.
      $menu.on('change', function() {
        Drupal.chart_suite.buildChart(chartId, data, this.value);
      });

      $(window).on('resize', function() {
        Drupal.chart_suite.buildChart(chartId, data, $menu.val());
      });

      // Initialize.
      Drupal.chart_suite.buildChart(chartId, data, $menu.val());
    },

    /*--------------------------------------------------------------------
     *
     * Configure.
     *
     *--------------------------------------------------------------------*/

    /**
     * Creates default Google Charts options for tabular data.
     *
     * @param data
     *   The data to render.
     *
     * @return
     *   Returns an options object ready for Google Charts.
     */
    getTableChartDefaults: function(data) {
      // Get the X and Y axis names, if any.
      var xaxis = data.xaxis;
      var yaxis = data.yaxis;

      if (xaxis == '') {
        xaxis = 'Category';
      }

      if (yaxis == '') {
        yaxis = 'Value';
      }

      // Create default charting options.
      var options = {
        // Plot title.
        title: data.title,
        titleTextStyle: {
          fontName: 'Verdana',
          bold:     true,
          italic:   true
        },
        fontName: 'Verdana',

        // X (horizontal) axis.
        hAxis: {
          title: xaxis,
          0: {
          }
        },

        // Y (vertical) axis.
        vAxis: {
          title: yaxis,
          0: {
            gridlines: {
              count: -1,
              color: '#CCC'
            }
          }
        },

        chartArea: {
          left:   '10%',
          right:  0,
          top:    10,
          bottom: 120,
        },

        pointSize: 4,

        legend: {
          position: 'bottom'
        },
      };

      if (data.col0 == 'string') {
        // Descrete X axis because column 0 are strings.
        // Show all labels and turn them sideways so that text will fit.
        options.hAxis[0].allowContainerBoundaryTextCutoff = true;
        options.hAxis[0].showTextEvery = 1;
      }
      else {
        // Continuous numeric X axis because column are numbers.
        // Add grid lines and enable pan/zoom.
        options.hAxis[0].gridlines = {
          count: -1,
          color: '#ccc'
        };
        options.enableInteractivity = true;
      }

      return options;
    },

    /**
     * Creates default Google Charts options for tree data.
     *
     * @param data
     *   The data to render.
     *
     * @return
     *   Returns an options object ready for Google Charts.
     */
    getTreeChartDefaults: function(data) {
      //
      // Create default charting options.
      var options = {
        // Plot title.
        title:     data.title,
        titleTextStyle: {
          fontName: 'Verdana',
          bold:     true,
          italic:   true
        },
        fontName:  'Verdana',

        explorer:  { },

        chartArea: {
          left:   '10%',
          right:  0,
          top:    10,
          bottom: 120,
        },

        legend: {
          position: 'bottom'
        },
      };

      return options;
    },

    /*--------------------------------------------------------------------
     *
     * Build.
     *
     *--------------------------------------------------------------------*/

    /**
     * Updates the way a table is shown on a menu select.
     *
     * @param id
     *   The DOM element ID.
     * @param data
     *   The data to render.
     * @param selection
     *   The current menu selection choosing the type of chart to render.
     */
    buildChart: function (id, data, selection) {
      var $chartDiv = $('#' + id);
      var div = document.getElementById(id);
      var chart;

      // Add chart-specific options and create the chart.
      switch (data.type) {
        case 'tree':
          var options = Drupal.chart_suite.getTreeChartDefaults(data);
          switch (selection) {
            case 'Org':
              chart = new google.visualization.OrgChart(div);
              options.allowCollapse = true;
              options.size = 'small';
              break;

            default:
              // Unknown selection.
              return;
          }
          break;

        case 'table':
          var options = Drupal.chart_suite.getTableChartDefaults(data);
          switch (selection) {
            case 'AreaStacked':
              chart = new google.visualization.AreaChart(div);
              options.isStacked = true;
              options.explorer = {
                actions: [ 'dragToPan', 'rightClickToReset' ],
                axis: 'horizontal',
              };
              break;

            case 'AreaNotStacked':
              chart = new google.visualization.AreaChart(div);
              options.isStacked = false;
              options.explorer = {
                actions: [ 'dragToPan', 'rightClickToReset' ],
                axis: 'horizontal',
              };
              break;

            case 'AreaStepped':
              chart = new google.visualization.SteppedAreaChart(div);
              options.isStacked = false;
              options.explorer = {
                actions: [ 'dragToPan', 'rightClickToReset' ],
                axis: 'horizontal',
              };
              break;

            case 'Bar':
              chart = new google.visualization.BarChart(div);
              options.explorer = {
                actions: [ 'dragToPan', 'rightClickToReset' ],
                axis: 'horizontal',
              };
              break;

            case 'Column':
              chart = new google.visualization.ColumnChart(div);
              options.explorer = {
                actions: [ 'dragToPan', 'rightClickToReset' ],
                axis: 'horizontal',
              };
              break;

            case 'Pie':
              chart = new google.visualization.PieChart(div);
              options.pieHole = 0.0;
              break;

            case 'Gauge':
              chart = new google.visualization.Gauge(div);
              break;

            case 'Donut':
              chart = new google.visualization.PieChart(div);
              options.pieHole = 0.4;
              break;

            case 'Histogram':
              chart = new google.visualization.Histogram(div);
              options.explorer = {
                actions: [ 'dragToPan', 'rightClickToReset' ],
                axis: 'horizontal',
              };
              break;

            case 'LinesStraight':
              chart = new google.visualization.LineChart(div);
              options.explorer = {
                actions: [ 'dragToPan', 'rightClickToReset' ],
                axis: 'horizontal',
              };
              break;

            case 'LinesCurved':
              chart = new google.visualization.LineChart(div);
              options.explorer = {
                actions: [ 'dragToPan', 'rightClickToReset' ],
                axis: 'horizontal',
              };
              options.curveType = 'function';
              break;

            case 'Scatter':
              chart = new google.visualization.ScatterChart(div);
              options.explorer = {
                actions: [ 'dragToPan', 'rightClickToReset' ],
                axis: 'horizontal',
              };
              break;

            case 'Table':
              chart = new google.visualization.Table(div);
              options.page = 'enable';
              options.alternatingRowStyle = true;
              options.width = '100%';
              break;

            default:
              // Unknown selection.
              return;
          }
          break;

        default:
          // Unknown data type.
          return;
      }

      // Draw the chart.
      chart.draw(data.data, options);
    },
  };
})(jQuery, Drupal, drupalSettings);
