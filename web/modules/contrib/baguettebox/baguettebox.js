/**
 * @file
 * BaguetteBox behaviours.
 */

(function ($, drupalSettings) {
  Drupal.behaviors.baguetteBox = {
    attach: function attach(context) {

      'use strict';

      var settings = drupalSettings.baguettebox;

      var captions = false;
      if (settings.captionsSource !== 'none') {
        captions = function (a) {
          var $img = $(a).find('img');
          var attribute = settings.captions_source === 'image_title' ?
            'title' : 'alt';
          return $img.attr(attribute);
        };
      }

      baguetteBox.run('.baguettebox', {
        captions: captions,
        animation: settings.animation,
        buttons: settings.buttons ? 'auto' : false,
        fullScreen: settings.full_screen,
        noScrollbars: settings.hide_scrollbars
      });

    }
  }
})(jQuery, drupalSettings);
