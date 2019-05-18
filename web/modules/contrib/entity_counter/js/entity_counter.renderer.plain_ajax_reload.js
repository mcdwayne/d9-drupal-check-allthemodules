/**
 * @file
 * JavaScript behaviors for 'Plain with ajax reload' entity counter renderer.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Provide plain entity counter value reload behavior.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior to reload a plain entity counter value.
   */
  Drupal.behaviors.entityCounterPlainAjaxReload = {
    attach: function (context) {
      var number_format = function(number, decimals, dec_point, thousands_sep){
        decimals = decimals || 0;
        number = parseFloat(number);

        if (!dec_point || !thousands_sep) {
          dec_point = ',';
          thousands_sep = '.';
        }

        var rounded_number = Math.round( Math.abs( number ) * ('1e' + decimals) ) + '';
        var numbers_string = decimals ? rounded_number.slice(0, decimals * -1) : rounded_number;
        var decimals_string = decimals ? rounded_number.slice(decimals * -1) : '';
        var formatted_number = "";

        while (numbers_string.length > 3){
          formatted_number += thousands_sep + numbers_string.slice(-3);
          numbers_string = numbers_string.slice(0,-3);
        }

        return (number < 0 ? '-' : '') + numbers_string + formatted_number + (decimals_string ? (dec_point + decimals_string) : '');
      };

      var entity_counters = drupalSettings['entity_counter']['plain_ajax_reload'];

      if (typeof entity_counters === 'undefined' || entity_counters.length === 0) {
        return;
      }

      // Add an interval for each configured counter.
      $.each(entity_counters, function($identifier, settings) {
        if (!$('#' + $identifier, context).length) {
          return;
        }

        // Make sure interval is a number, otherwise use 30.
        var interval = isNaN(settings['interval']) ? 30 : settings['interval'];

        setInterval(function() {
          $.ajax(settings['url'])
            .done(function (data) {
              var new_value = data * settings['ratio'];
              if (settings['round'] === 'up') {
                new_value = Math.ceil(new_value);
              }
              else if (settings['round'] === 'down') {
                new_value = Math.floor(new_value);
              }

              if (settings['format']) {
                new_value = number_format(new_value, settings['format']['decimals'], settings['format']['type-decimal'], settings['format']['separator'])
              }
              else {
                // @TODO Add a new setting fot this.
                new_value = Math.trunc(new_value);
              }

              // Only refresh if the new value is different.
              var new_value_str = new_value + '';
              if ($('#' + $identifier, context).html().replace(/[^0-9]/g, '') !== new_value_str.replace(/[^0-9]/g, '')) {
                $('#' + $identifier, context).html(new_value);
              }
            });
        }, interval * 1000);
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
