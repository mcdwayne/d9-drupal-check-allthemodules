/**
 * @file
 * This file is basically provides the jquery function.
 */

(function ($) {
  'use strict';
  Drupal.behaviors.jquery_videobackground = {
    attach: function (context, settings) {
      $('.counter').counterUp({
            delay: settings.counter_up.delay,
            time: settings.counter_up.total_time,
        });
    }
  };
}(jQuery));
