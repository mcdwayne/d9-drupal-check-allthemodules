/**
 * @file
 * Bars chart views style drawing.
 */

(function ($) {
  Drupal.behaviors.ChartViewsStyleBars = {
    attach: function (context) {
      // Run col charts.
      var $charts = $('.charting_chart-bars_chart');

      $.each($charts, function (item, chart) {
        var $chart = $(this);
        var $chartBars = $chart.find('.charting_chart-bar');
        var totalCols = $chart.attr('data-cols');
        var colWidth = (95 / totalCols);
        var colSep = (100 / totalCols) - colWidth;

        // Set dynamic column width.
        $chart.find('.charting_chart-col').each(function (i, item) {
          $(item).css({
            width: colWidth + "%",
            'margin-left': colSep + "%"
          });
        });

        // Recalculate values.
        var max = 0;
        $chartBars.each(function (i, item) {
          // Get maximum value.
          if (parseFloat($(item).attr('data-value')) > max) {
            max = parseFloat($(item).attr('data-value'));
          }
        });

        // Draw bars.
        $chartBars.each(function (i, item) {
          var value = parseFloat($(item).attr('data-value'));
          var percent = (value * 100) / max;

          $(item).css({
            height: percent + "%",
            transition: "height 1s cubic-bezier(0.77, 0, 0.175, 1)",
            opacity: "1"
          });
        });
      });
    }
  };
})(jQuery);
