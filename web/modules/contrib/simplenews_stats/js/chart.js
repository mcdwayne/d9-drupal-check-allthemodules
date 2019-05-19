(function ($, Drupal) {

  if (!$.simplenews_stats)
    $.simplenews_stats = new Object();

  $.simplenews_stats.chart = function (el, options) {
    var base = this;

    base.options = $.extend({}, $.simplenews_stats.chart.defaultOptions, options);
    base.$el = $(el);
    base.el = el;
    base.chart;

    base.$el.data("simplenews_stats.chart", base);

    base.init = function () {

      base.chart = new Chart(base.$el, {
        type: 'line',
        data: base.options,
        options: {
          scales: {
            yAxes: [{
                ticks: {
                  beginAtZero: true
                }
              }]
          }
        }
      });


    };
    base.init();
  };

  $.simplenews_stats.chart.defaultOptions = {
  };

  $.fn.simplenews_stats_chart = function (options) {
    this.each(function () {
      var instance = $(this).data("simplenews_stats.chart");
      if (instance === undefined)
        new $.simplenews_stats.chart(this, options);
    });
    return this;
  };

  $.fn.get_simplenews_stats_chart = function () {
    this.data("simplenews_stats.chart");
  };

  Drupal.behaviors.simplenews_stats_chart = {
    attach: function (context, settings) {
      $.each(settings.simplenews_stats, function (selector, data) {
        $('#' + selector).simplenews_stats_chart(data);
      });
    }
  };

})(jQuery, Drupal);
