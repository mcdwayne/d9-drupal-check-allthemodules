/**
 * @file
 * JavaScript file for the cache_clear_shortcut module.
 */

(function ($, Drupal, drupalSettings) {
  // Remap the filter functions for autocomplete to recognise the
  // extra value "command".
  'use strict';
  Drupal.behaviors.cache_clear_shortcut = {
    attach: function () {
      $('body').once('cache_clear_shortcut').each(function () {
        var path = Drupal.url(drupalSettings.cache_clear_shortcut.image_path);
        var imageUrl = '<img class="cache-load cache-center" src="' + path + '"/>';
        // Key events.
        $(document).keydown(function (event) {
          if (event.altKey === true && event.keyCode === 67) {
            $('body').prepend(imageUrl);
            $.ajax({
              url: Drupal.url('admin/config/development/performance/clearcache'),
              dataType: 'json',
              success: function (data) {
                $('.cache-load').hide();
                $('body').prepend('<div class="overlay"><div class="popup"><h2>Cache cleared.</h2><a class="close" href="#">&times;</a></div></div>');
                $('.overlay .close').click(function () {
                  $('.overlay').fadeOut();
                });
                setTimeout(function () {
                  $('.overlay').fadeOut();
                }, 1000);
              }
            });
          }
        });
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
