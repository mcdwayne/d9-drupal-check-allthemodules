/**
 * @file
 */

(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.flot_examples = {
    attach: function () {
      var data = drupalSettings.flot.placeholder.data;
      $('#whole').click(function () {
        $.plot('#placeholder', data, {
          xaxis: {mode: 'time'}
        });
      });
      $('#nineties').click(function () {
        $.plot('#placeholder', data, {
          xaxis: {
            mode: 'time',
            min: (new Date(1990, 0, 1)).getTime(),
            max: (new Date(2000, 0, 1)).getTime()
          }
        });
      });

      $('#latenineties').click(function () {
        $.plot('#placeholder', data, {
          xaxis: {
            mode: 'time',
            minTickSize: [1, 'year'],
            min: (new Date(1996, 0, 1)).getTime(),
            max: (new Date(2000, 0, 1)).getTime()
          }
        });
      });

      $('#ninetyninequarters').click(function () {
        $.plot('#placeholder', data, {
          xaxis: {
            mode: 'time',
            minTickSize: [1, 'quarter'],
            min: (new Date(1999, 0, 1)).getTime(),
            max: (new Date(2000, 0, 1)).getTime()
          }
        });
      });

      $('#ninetynine').click(function () {
        $.plot('#placeholder', data, {
          xaxis: {
            mode: 'time',
            minTickSize: [1, 'month'],
            min: (new Date(1999, 0, 1)).getTime(),
            max: (new Date(2000, 0, 1)).getTime()
          }
        });
      });

      $('#lastweekninetynine').click(function () {
        $.plot('#placeholder', data, {
          xaxis: {
            mode: 'time',
            minTickSize: [1, 'day'],
            min: (new Date(1999, 11, 25)).getTime(),
            max: (new Date(2000, 0, 1)).getTime(),
            timeformat: '%a'
          }
        });
      });

      $('#lastdayninetynine').click(function () {
        $.plot('#placeholder', data, {
          xaxis: {
            mode: 'time',
            minTickSize: [1, 'hour'],
            min: (new Date(1999, 11, 31)).getTime(),
            max: (new Date(2000, 0, 1)).getTime(),
            twelveHourClock: true
          }
        });
      });
    }
  };
}(jQuery, Drupal, drupalSettings));
