/**
 * @file
 *
 * Farbtastic color selector.
 */

(function ($) {
  'use strict';
  Drupal.behaviors.imageEffectsFarbtasticColorSelector = {
    attach: function (context, settings) {
      $('.collageformatter-farbtastic-color-selector', context).once('collageformatter-farbtastic-color-selector').each(function (index) {
        // Configure picker to be attached to the text field.
        var target = $(this).find('.collageformatter-color-textfield');
        var picker = $(this).find('.farbtastic-colorpicker');
        $.farbtastic($(picker), target);
      });
    }
  };
})(jQuery);
