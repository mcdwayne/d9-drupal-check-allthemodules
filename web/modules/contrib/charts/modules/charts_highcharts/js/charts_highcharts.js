/**
 * @file
 * JavaScript integration between Highcharts and Drupal.
 */
(function ($) {
  'use strict';

  Drupal.behaviors.chartsHighcharts = {
    attach: function (context, settings) {

      $('.charts-highchart').once().each(function () {
        if ($(this).attr('data-chart')) {
          var highcharts = $(this).attr('data-chart');
          var hc = JSON.parse(highcharts);
          if (hc.chart.type === 'pie') {
            delete hc.plotOptions.bar;
            hc.plotOptions.pie = {
              allowPointSelect: true,
              cursor: 'pointer',
              showInLegend: true,
              dataLabels: {
                enabled: true,
                format: '{point.y:,.0f}'
              }
            };

            hc.legend.enabled = true;
            hc.legend.labelFormatter = function () {
              var legendIndex = this.index;
              return this.series.chart.axes[0].categories[legendIndex];
            };

            hc.tooltip.formatter = function () {
              var sliceIndex = this.point.index;
              var sliceName = this.series.chart.axes[0].categories[sliceIndex];
              return '' + sliceName +
                  ' : ' + this.y + '';
            };

          }

          $(this).highcharts(hc);
        }
      });
    }
  };
}(jQuery));
