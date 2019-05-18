/**
 * @file
 * Simple JavaScript call tracking.
 */

(function ($, Drupal, settings) {

  "use strict";

  Drupal.behaviors.main = {
    attach: function (context) {
      // Get url query.
      var urlParams = new URLSearchParams(window.location.search);

      // Get telephones with utm labels
      var telArr = drupalSettings.telephones;
      for (let p of urlParams) {
        var key = p[0];
        var val = p[1];
        if (telArr[key]) {
          var telephones = telArr[key][val];
          for (var tel in telephones) {
            // Search telephone for change
            $(document.body).find('*').each(function () {
              var text = $(this).html();
              text = text.replace(tel, telephones[tel]);
              $(this).html(text);
            });
          }
        }
      }
    }
  }

})(jQuery, Drupal, drupalSettings);
