/**
 * @file
 * Adds radioactivity triggers.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Add Radioactivity triggers.
   */
  Drupal.behaviors.radioactivityTriggers = {
    attach: function (context) {
      // Only run on page load, not for subsequent ajax loads.
      if (typeof context == 'object' && context.toString().indexOf('HTMLDocument') != -1) {
        var emits = [], emit, i = 0;

        while (emit = drupalSettings['ra_emit_' + i]) {
          emits.push(JSON.parse(emit));
          i++;
        }

        $.ajax({
          type: "POST",
          url: drupalSettings.radioactivity.endpoint,
          data: JSON.stringify(emits),
          success: function (data) {
            if (data.status != 'ok') {
              console.error('Radioactivity: ' + data.message);
            }
          },
          dataType: 'json'
        });

      }
    }
  }
})(jQuery, Drupal, drupalSettings);
