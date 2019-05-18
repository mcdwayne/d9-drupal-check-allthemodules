(function ($, Drupal, drupalSettings) {
  /**
   * Load google charting package and call the
   * function to draw the charts.
   */
  google.load("visualization", "1", {packages:["corechart"]});
  google.setOnLoadCallback(drawCharts);
  function drawCharts() {
    // Watchdog chart.
    drawWatchdogChart(0);

    // Apache chart.
    drawApacheChart(0);
  }

  /**
   * Function for facilitating to draw watchdog chart.
   *
   * @param filter
   */
  function drawWatchdogChart(filter) {
    var watchdogAgainst = $('#edit-watchdog-against').val();
    var chartData = eval(eval('drupalSettings.logmanStatistics.' + watchdogAgainst));
    if (filter == 1) {
      chartData = dataFilter(chartData);
      drawChart(chartData, watchdogAgainst, drupalSettings.logmanStatistics.watchdogPlaceholder);
    }
    else {
      drawChart(chartData, watchdogAgainst, drupalSettings.logmanStatistics.watchdogPlaceholder);
      drawTable(chartData, drupalSettings.logmanStatistics.watchdogTablePlaceholder, drupalSettings.logmanStatistics.watchdogDataSelector);
    }
  }

  /**
   * Function for facilitating to draw apache chart.
   *
   * @param filter
   */
  function drawApacheChart(filter) {
    var apacheAgainst = $('#edit-apache-against').val();
    var chartData = eval(eval('drupalSettings.logmanStatistics.' + apacheAgainst));
    if (filter == 1) {
      chartData = dataFilter(chartData);
      drawChart(chartData, apacheAgainst, drupalSettings.logmanStatistics.apachePlaceholder);
    }
    else {
      drawChart(chartData, apacheAgainst, drupalSettings.logmanStatistics.apachePlaceholder);
      drawTable(chartData, drupalSettings.logmanStatistics.apacheTablePlaceholder, drupalSettings.logmanStatistics.apacheDataSelector);
    }
  }

  /**
   * Function to draw a chart.
   *
   * @param chartData
   * @param chartTitle
   * @param placeHolder
   */
  function drawChart(chartData, chartTitle, placeHolder) {
    // Check if the charting data is correct then draw the
    // chart otherwise display error.
    if (chartData.length > 1) {
      var data = google.visualization.arrayToDataTable(chartData);
      var options = {
        title: chartTitle.toUpperCase(),
        width: 700,
        height: 200
      };
      var chart = new google.visualization.ColumnChart(document.getElementById(placeHolder));
      chart.draw(data, options);

      // Set the chart position as relative.
      document.getElementById(placeHolder).childNodes[0].childNodes[0].style.position = 'relative';
    }
    else {
      document.getElementById(placeHolder).innerHTML = 'Error in charting data.';
    }
  }

  /**
   * Function to create the charting data table.
   *
   * @param chartData
   * @param placeHolder
   * @param dataSelector
   */
  function drawTable(chartData, placeHolder, dataSelector) {
    var dataTable = '';
    for (var index = 1; index < chartData.length; index++) {
      dataTable += '<p><input type="checkbox" id="' + chartData[index][0].replace(/\s+/g, '_') + '" class="' + dataSelector + '" checked>';
      dataTable += '<b>' + chartData[index][0] + ':</b> ' + chartData[index][1] + '</p>'
    }
    dataTable += '<div class="logman_clear"></div>';
    $('#' + placeHolder).html(dataTable);
  }

  /**
   * Function to filter the charting data set
   * base on user's selection.
   *
   * @param chartData
   * @returns {*}
   */
  function dataFilter(chartData) {
    var filteredData = Array();
    filteredData[0] = chartData[0];
    for (var index = 1; index < chartData.length; index++) {
      if ($('#' + chartData[index][0].replace(/\s+/g, '_')).is(':checked') == true) {
        filteredData[filteredData.length] = chartData[index];
      }
    }
    return filteredData;
  }

    Drupal.behaviors.logman = {
        attach: function (context, settings) {
      // Show different chart whenever a different value for against is selected.
      $('#edit-watchdog-against').change(function (){
        drawWatchdogChart();
      });
      $('#edit-apache-against').change(function (){
        drawApacheChart();
      });

      // Change the chart based on selected charting data.
      $('#' + settings.logmanStatistics.watchdogTablePlaceholder).bind("DOMSubtreeModified", function() {
        $('.' + settings.logmanStatistics.watchdogDataSelector).change(function (){
          drawWatchdogChart(1);
          return false;
        });
      });
      $('#' + settings.logmanStatistics.apacheTablePlaceholder).bind("DOMSubtreeModified", function() {
        $('.' + settings.logmanStatistics.apacheDataSelector).change(function (){
          drawApacheChart(1);
          return false;
        });
      });
    }
  };
}(jQuery, Drupal, drupalSettings));
