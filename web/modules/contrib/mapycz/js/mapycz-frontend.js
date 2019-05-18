/**
 * @file
 * Mapycz frontend.
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.mapyczBehavior = {

    attach: function (context, settings) {
      Drupal.mapycz.mapsInit({ }, context);
    }

  };

})(jQuery, Drupal);
