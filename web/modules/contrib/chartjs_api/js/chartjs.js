/**
 * @file
 * Contains js for chartjs.
 */

(function ($) {

  'use strict';

  var ChartJsInstances = {};

  var ChartJs = {

    /**
     * Id graph.
     */
    id: null,

    /**
     * JS object.
     */
    el: null,

    /**
     * jQuery object.
     */
    ctx: null,

    /**
     * Graph type.
     */
    type: null,

    /**
     * Graph default options.
     */
    options: {
      legend: {
        position: 'bottom',
        labels: {
          boxWidth: 12
        }
      },
      responsiveAnimationDuration: 500
    },

    /**
     * Graph custom options.
     */
    custom_options: {},

    /**
     * Enabled plugins.
     */
    plugins: [],

    /**
     * Object with data.
     */
    data: {},

    /**
     * Init method.
     *
     * @param {string} id
     *   Id of graph.
     * @param {object} data
     *   Object with labels and datasets.
     * @param {object} options
     *   Object with custom options.
     * @param {array} plugins
     *   Object with plugins.
     */
    init: function (id, data, options, plugins) {
      // Set id.
      this.id = id;

      // Get canvas jquery object.
      this.el = $('#' + id);

      // Get canvas js object.
      this.ctx = this.el[0];

      // Build data.
      this.buildData(data);

      // Set custom options.
      this.custom_options = options;

      // Set plugins.
      var plugins_a = [];
      $.each(plugins, function (i, el) {
        plugins_a.push(window[el]);
      });
      this.plugins = plugins_a;

      // Build graph.
      this.buildGraph(this.el.attr('data-graph-type'));
    },

    /**
     * Build data object.
     *
     * @param {object} data
     *   Object with labels and datasets.
     */
    buildData: function (data) {
      this.data = data;
    },

    /**
     * Build graph.
     *
     * @param {string} type
     *   Graph type.
     */
    buildGraph: function (type) {
      switch (type) {
        case 'halfdonut':
          this.halfdonut();
          break;

        default:
          this.type = type;
      }

      // Extend options.
      $.extend(true, this.options, this.custom_options);

      // Destroy old charts.
      if (typeof ChartJsInstances[this.id] !== 'undefined') {
        ChartJsInstances[this.id].destroy();
      }

      // Create new chart.
      ChartJsInstances[this.id] = new Chart(this.ctx, {
        type: this.type,
        data: this.data,
        options: this.options,
        plugins: this.plugins
      });

    },

    // Set halfdonut settings.
    halfdonut: function () {
      // Set graph type.
      this.type = 'doughnut';
      // Set options.
      this.options.rotation = Math.PI;
      this.options.circumference = Math.PI;
    }
  };

  Drupal.behaviors.chartjs = {
    attach: function (context, settings) {
      // Check if exist charts to build.
      if (settings.chartjs) {
        $.each(settings.chartjs, function (i, el) {
          ChartJs.init(i, el.data, el.options, el.plugins);
        });
        delete settings.chartjs;
      }
    }
  };
})(jQuery);
