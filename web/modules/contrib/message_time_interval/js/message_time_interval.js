(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.message_time_interval = {
    attach: function (context, settings) {
      if (drupalSettings.message_time_interval.message_enabled) {
        jQuery('.messages').delay(drupalSettings.message_time_interval.message_fadeOut)[drupalSettings.message_time_interval.message_effect]('slow');
      }
    }
  };
})(jQuery, Drupal);
