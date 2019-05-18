/**
 * @file
 * Instanciate jquery minicolors library.
 */

(function ($, Drupal) {
    'use strict';

  Drupal.behaviors.jquery_minicolors = {
    attach: function(context) {

      $('input.mini-colors', context).each(function () {
        var settings = {
          control: $(this).data('control') || 'hue',
          format: $(this).data('format') || 'hex',
          keywords: $(this).data('keywords') || '',
          inline: $(this).data('inline') ? true : false,
          letterCase: $(this).data('letter-case') || 'lowercase',
          opacity: $(this).data('opacity') ? true : false,
          position: $(this).data('position') || 'bottom left',
          swatches: $(this).data('swatches') ? $(this).data('swatches').split('|') : [],
          animationSpeed: $(this).data('animation-speed') || 0,
          animationEasing: $(this).data('animation-easing') || 'swing',
          theme: $(this).data('theme') || 'default',
          changeDelay: $(this).data('change-delay') || 0,
          hideSpeed: $(this).data('hide-speed') || 100,
          showSpeed: $(this).data('show-speed') || 100,
        };

        $(this).minicolors(settings);

        // We set the custom size defined in the widget and override size added
        // by jQuery minicolors by default.
        if ($(this).data('size')) {
          $(this).prop('size', $(this).data('size'));
        }

      });

    }
  };

}(jQuery, Drupal));
