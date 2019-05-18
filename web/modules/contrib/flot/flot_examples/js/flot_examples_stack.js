/**
 * @file
 */

(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.flot_examples = {
    attach: function () {
      var data = drupalSettings.flot.placeholder.data;
      var options = drupalSettings.flot.placeholder.options;
      var stack = 0;
      var bars = true;
      var lines = false;
      var steps = false;

      function plotWithOptions() {
        options.series.stack = stack;
        options.series.lines.show = lines;
        options.series.lines.steps = steps;
        options.series.bars.show = bars;
        $.plot('#placeholder', data, options);
      }

      $('.stackControls input').click(function (e) {
        e.preventDefault();
        stack = this.id === 'stacking' ? true : null;
        plotWithOptions();
      });

      $('.graphControls input').click(function (e) {
        e.preventDefault();
        bars = this.id === 'Bars';
        lines = this.id === 'Lines' || this.id === 'steps';
        steps = this.id === 'steps';
        plotWithOptions();
      });

    }
  };
}(jQuery, Drupal, drupalSettings));
