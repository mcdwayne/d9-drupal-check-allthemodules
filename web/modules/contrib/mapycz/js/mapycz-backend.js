/**
 * @file
 * Mapycz backend.
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.mapyczBehavior = {

    attach: function (context, settings) {
      var layers = {
        'basic': 'Základní',
        'turist': 'Turistická',
        'satelite': 'Satelitní',
      };
      Drupal.mapycz.mapsInit({ admin: true, suggest: true, layerOptions: layers }, context);
    }

  };

})(jQuery, Drupal);
