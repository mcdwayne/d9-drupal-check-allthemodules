/**
 * @file
 * Attaches the behaviors for shuffle module.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.shuffle_popup = {
    attach: function (context) {
      // Gallery.
      $(context).find('.mfp-all-items, .mfp-first-item, .mfp-random-item').once('mfp-processed').each(function () {
        $(this).magnificPopup({
          delegate: 'a',
          type: 'image',
          gallery: {
            enabled: true
          },
          image: {
            titleSrc: function (item) {
              return item.img.attr('alt');
            }
          },

          mainClass: 'mfp-fade'

        });
      });

      // Separate items.
      $(context).find('.mfp-separate-items').once('mfp-processed').each(function () {
        $(this).magnificPopup({
          delegate: 'a',
          type: 'image',
          image: {
            titleSrc: function (item) {
              return item.img.attr('alt');
            }
          },

          mainClass: 'mfp-fade'

        });
      });
    }
  };

})(jQuery, Drupal);
