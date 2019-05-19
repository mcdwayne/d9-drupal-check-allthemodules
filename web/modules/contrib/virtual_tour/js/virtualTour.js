(function ($, Drupal, drupalSettings) {
  'use strict';

  /**
   * Initialize pannellum functionality.
   */
  Drupal.behaviors.pannellum = {
    attach: function (context, drupalSettings) {
      $('.panorama').once('virtual_tour').each(function (index) {
        var id = $(this).attr('id');
        var effectType = drupalSettings.virtual_tour[index].type;
        var imgSrc = drupalSettings.virtual_tour[index].src;
        var autoload = drupalSettings.virtual_tour[index].autoload;
        function autoloadCheck() {
          return !!autoload;
        }
        pannellum.viewer(id, {
          type: effectType,
          panorama: imgSrc,
          autoLoad: autoloadCheck()
        });
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
