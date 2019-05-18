/**
 * @file
 */

(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.zoom = {
    attach: function (context, settings) {
      var original_urls = drupalSettings.imageFieldZoom.image_urls;
      $('.image-zoom-container img.original-image').each(function () {
        $(this).once('init').parent('.image-zoom').zoom({
          url: original_urls[$(this).attr('fid')],
          on: drupalSettings.imageFieldZoom.image_zoom_style,
          touch: drupalSettings.imageFieldZoom.image_touchscreen_compatible,
          magnify: drupalSettings.imageFieldZoom.image_magnify,
          duration: drupalSettings.imageFieldZoom.image_fade_duration
        });
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
