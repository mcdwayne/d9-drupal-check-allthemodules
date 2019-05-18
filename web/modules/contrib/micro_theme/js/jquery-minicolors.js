/**
 * @file
 * Instanciate jquery minicolors library.
 */

(function ($, Drupal) {
    'use strict';

  Drupal.behaviors.usine_theme_jquery_minicolors = {
    attach: function(context) {

      $('#edit-color-palette input[type="text"]', context).each(function () {
        var settings = {
          control: 'hue',
          format: 'hex',
          keywords: '',
          inline: false,
          letterCase: 'lowercase',
          opacity: false,
          position: 'bottom left',
          swatches: [],
          animationSpeed: 0,
          animationEasing: 'swing',
          theme: 'default',
          changeDelay: 0,
          hideSpeed: 100,
          showSpeed: 100
        };

        $(this).minicolors(settings);

        // We set the custom size defined in the widget and override size added
        // by jQuery minicolors by default.
        $(this).prop('size', 30);

      });

    }
  };

}(jQuery, Drupal));
