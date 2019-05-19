(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.kpiAnalyticsRenderMorris = {
    attach: function (context, settings) {
      $(context).find('div.morris_chart').once('renderChart').each(function () {
        var uuid = $(this).attr('id'),
            options = settings.kpi_analytics.morris.chart[uuid].options;

        if (!options.plugin) {
          options.plugin = 'Line';
        }

        options.xLabelFormat = function(x) {
          return {
            'label': x.label,
            'highlight': !!x.src.highlight
          };
        };

        var Morris = $.extend(true, {}, window.Morris);

        Morris[options.plugin].prototype.drawXAxisLabel = function(xPos, yPos, text) {
          var element;

          element = this.raphael.text(xPos, yPos, text.label)
            .attr('font-size', this.options.gridTextSize)
            .attr('font-family', this.options.gridTextFamily)
            .attr('font-weight', this.options.gridTextWeight)
            .attr('fill', this.options.gridTextColor);

          if (text.highlight) {
            element.attr('class', 'morris-label-highlight');
          }

          return element;
        };

        Morris[options.plugin].prototype.hoverContentForRow = function(index) {
          var j, y;
          var row = this.data[index];

          var $content = $('<div class="morris-hover-row-label">').html(row.label.label);

          if (row.label.highlight) {
            $content.addClass('morris-label-highlight');
          }

          var content = $content.prop('outerHTML');

          for (j in row.y) {
            y = row.y[j];

            if (!this.options.labels[j]) {
              continue;
            }

            content += '<div class="morris-hover-point">' +
              '<span class="morris-hover-marker" style="background-color: ' + this.colorFor(row, j, 'label') + '"></span>' +
              this.options.labels[j] + ': ' +
              this.yLabelFormat(y, j) +
              '</div>';
          }

          if (typeof this.options.hoverCallback === 'function') {
            content = this.options.hoverCallback(index, this.options, content, row.src);
          }

          return [content, row._x, row._ymax];
        };

        new Morris[options.plugin](options);
      });
    }
  };

})(jQuery, Drupal);
