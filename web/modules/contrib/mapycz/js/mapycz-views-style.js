/**
 * @file
 * Mapycz views style.
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.mapyczBehavior = {

    attach: function (context, settings) {
      Drupal.mapycz.mapsInit({ computeCenterZoom: true }, context);
    }

  };

})(jQuery, Drupal);
