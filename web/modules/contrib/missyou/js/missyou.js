/**
 * @file
 * Init iMissYou library.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.missyou = {
    attach: function (context, settings) {

      $(document).ready(function () {
        $.iMissYou({
          title: drupalSettings.missyou.missyou_title,
          favicon: {
            enabled: drupalSettings.missyou.missyou_show_favicon,
            src: drupalSettings.missyou.missyou_favicon
          }
        });
      });

    }
  };

})(jQuery, Drupal, drupalSettings);
