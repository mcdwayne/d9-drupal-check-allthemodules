/**
 * @file
 * Map type style.
 */

/**
 * @typedef {Object} MapTypeStyleSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @property {String} style
 */

(function ($, Drupal) {

  'use strict';

  /**
   * MapTypeStyleSettings.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches MapTypeStyleSettings functionality to relevant elements.
   */
  Drupal.behaviors.geolocationGoogleMapTypeStyle = {
    attach: function (context, drupalSettings) {
      Drupal.geolocation.executeFeatureOnAllMaps(
        'map_type_style',

        /**
         * @param {GeolocationGoogleMap} map - Current map.
         * @param {MapTypeStyleSettings} featureSettings - Settings for current feature.
         */
        function (map, featureSettings) {
          map.addInitializedCallback(function (map) {

            var styles = [];
            if (typeof map.googleMap.styles !== 'undefined') {
              styles = map.googleMap.styles;
            }
            styles = $.merge(featureSettings.style, styles);

            map.googleMap.setOptions({styles: styles});
          });

          return true;
        },
        drupalSettings
      );
    },
    detach: function (context, drupalSettings) {}
  };
})(jQuery, Drupal);
