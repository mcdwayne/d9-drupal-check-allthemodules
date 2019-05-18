/**
 * @file
 * Author: Synapse-studio.
 */

(function ($) {
  $(document).ready(function () {
    google.charts.load('current', {'packages':['corechart', 'bar']});
    google.charts.setOnLoadCallback(drawChart);

    function drawChart() {
      var div = document.getElementById(drupalSettings.onlinepbx.div);
      var data = drupalSettings.onlinepbx.chartsData;
      var options = {
        height: drupalSettings.onlinepbx.height,
        legend: {
          position: 'bottom'
        },
        bar: {
          groupwidth: '95%',
        },
      };
      draw_bars(div, data, options);
    }

    function draw_bars(div, data, options) {
      var data = google.visualization.arrayToDataTable(data);
      var chart = new google.charts.Bar(div);
      chart.draw(data, options);
    }
  });
})(this.jQuery);
