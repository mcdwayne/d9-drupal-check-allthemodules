/**
 * @file
 * Provides Mason loader.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.mason = {
    attach: function (context) {

      $('.mason', context).once('mason').each(function () {
        var elm = $(this);
        var options = $.extend({}, drupalSettings.mason, elm.data('mason'));

        elm.mason(options);
      });

    }
  };

})(jQuery, Drupal, drupalSettings);
