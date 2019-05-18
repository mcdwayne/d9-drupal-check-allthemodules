/**
 * @file
 * Integrates chartist library using Drupal js settings.
 */

(function ($) {
  'use strict';

  Drupal.behaviors.custom_charts_chartist = {
    attach: function (context, settings) {

      $('.chartist').once('chartist_init').each(function (index) {
        var chart_id = $(this).attr('id');
        var settings = Drupal.settings[chart_id];
        if (settings.data.series.length) {
          var chart_object = {};

          // Series.
          chart_object.series = [];
          $.each(settings.data.series, function (index) {
            chart_object.series[index] = this;
          });

          // Labels.
          if (typeof (settings.data.labels) != 'undefined') {
            chart_object.labels = settings.data.labels;
          }

          // Additional settings.
          if (typeof (settings.settings) == 'undefined') {
            settings.settings = {};
          }

          var chart = new Chartist[settings.type]('#' + chart_id, chart_object, settings.settings);

          var $chart = $('#' + chart_id);

          // Allow other modules to interact when the chart is created.
          chart.on('created', function (data) {
            var event = jQuery.Event('chartist');
            event.chart = chart;
            $(document).trigger(event);
          });

          // Add special data to points, if provided.
          if (typeof (settings.data.featured_points) != 'undefined' && settings.data.featured_points.length) {
            chart.on('draw', function (data) {
              if (data.type === 'point') {
                for (var i = 0; i < settings.data.featured_points.length; ++i) {
                  var element = settings.data.featured_points[i];
                  if (data.seriesIndex === element[0] && data.index === element[1]) {
                    data.element.addClass('featured');
                  }
                }
              }
            });
          }

          // Animation.
          if (settings.settings.animate) {
            chart.on('draw', function (data) {
              if (data.type === 'line' || data.type === 'area') {
                data.element.animate({
                  d: {
                    begin: 2000 * data.index,
                    dur: 2000,
                    from: data.path.clone().scale(1, 0).translate(0, data.chartRect.height()).stringify(),
                    to: data.path.clone().stringify(),
                    easing: Chartist.Svg.Easing.easeOutQuint
                  }
                });
              }
            });
          }

          // On/off function.
          if (typeof (settings.settings.onoff) != 'undefined' && settings.settings.onoff) {
            var onoff = $('.chartist-series-onoff[for="' + chart_id + '"]');
            onoff.find('input').each(function (index) {
              $(this).change(function (event) {
                var $serie = $chart.find($(this).attr('data-serie')).first();
                if (this.checked) {
                  $serie.css('display', '');
                }
                else {
                  $serie.css('display', 'none');
                }
              });
            });
          }

          // Tooltip logic.
          var show_tooltips = false;
          if (typeof (settings.settings.show_tooltips) != 'undefined') {
            show_tooltips = settings.settings.show_tooltips;
          }
          else if (typeof (settings.settings.tooltip_schema) != 'undefined') {
            show_tooltips = true;
          }

          if (show_tooltips) {
            var $toolTip = $chart
              .append('<div class="tooltip"></div>')
              .find('.tooltip')
              .hide();

            var point_selector = '';
            switch (settings.type) {
              case 'Line': point_selector = '.ct-point'; break;

              case 'Bar': point_selector = '.ct-bar'; break;

              case 'Pie': point_selector = '.ct-slice-pie'; break;
            }

            $chart.on('mouseenter', point_selector, function () {
              var $point = $(this);
              var y_value = $point.attr('ct:value');
              var x_value;
              var seriesName = $point.parent().attr('ct:series-name');
              var tooltip_html = '';
              var point_index = $point.index() - 1;

              if (typeof (chart_object.labels[point_index]) != 'undefined') {
                x_value = chart_object.labels[point_index];
              }
              else {
                x_value = false;
              }
              if (typeof (settings.settings.tooltip_schema) != 'undefined') {
                tooltip_html = settings.settings.tooltip_schema;
                if (x_value !== false) {
                  tooltip_html = tooltip_html.replace(/\[x\]/g, x_value);
                }
                tooltip_html = tooltip_html.replace(/\[y\]/g, y_value) . replace(/\[serie\]/g, seriesName);
              }
              else {
                tooltip_html = seriesName + '<br>' + y_value;
              }
              $toolTip.html(tooltip_html) . show() . css('opacity', 1);
            });

            $chart.on('mouseleave', point_selector, function () {
              $toolTip.hide();
            });

            $chart.on('mousemove', function (event) {
              var left = (event.originalEvent.layerX || event.offsetX) - $toolTip.width() / 2 - 10;
              var top = (event.originalEvent.layerY || event.offsetY) - $toolTip.height() - 40;
              $toolTip.css({
                left: left,
                top: top
              });
            });
          }

        }
      });
    }
  };
})(jQuery);
