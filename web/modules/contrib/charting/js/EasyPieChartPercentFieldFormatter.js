/**
 * @file
 * Easy Pie Chart Percent Field Formatter drawing.
 */

(function ($) {
  Drupal.behaviors.EasyPieChartPercentFieldFormatter = {
    attach: function (context) {
      // Fire the library functionality.
      $.each($('.EasyPieChartPercentFieldFormatter'), function (i, item) {
        var $pie = $(this);
        $pie.easyPieChart({
          size: drupalSettings.charting[$pie.attr('id')].size,
          animate: drupalSettings.charting[$pie.attr('id')].animate,
          lineWidth: drupalSettings.charting[$pie.attr('id')].line_width,
          barColor: drupalSettings.charting[$pie.attr('id')].barcolor,
          trackColor: drupalSettings.charting[$pie.attr('id')].trackcolor,
          scaleColor: drupalSettings.charting[$pie.attr('id')].scalecolor,
          lineCap: drupalSettings.charting[$pie.attr('id')].linecap
        });
      });
    }
  };
})(jQuery);
