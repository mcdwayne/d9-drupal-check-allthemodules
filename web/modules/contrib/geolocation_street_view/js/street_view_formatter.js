/**
 * @file
 * Geolocation Street View formatter Javascript.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Attach Google Street View formatter functionality.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches Google Street View formatter functionality to relevant elements.
   */
  Drupal.behaviors.geolocationStreetViewFormatter = {
    attach: function (context, drupalSettings) {
      if (typeof Drupal.geolocation.loadGoogle === 'function') {
        // First load the library from google.
        Drupal.geolocation.loadGoogle(function () {
          initialize(drupalSettings.geolocation.maps, context);
        });
      }
    }
  };

  /**
   * Runs after the Google Maps API is available
   *
   * @param {GeolocationMap[]} mapSettings - The geolocation map objects.
   * @param {object} context - The html context.
   */
  function initialize(mapSettings, context) {
    $.each(
      mapSettings,

      /**
       * @param {string} mapId - Current map ID
       * @param {GeolocationMap} map - Single map settings Object
       */
      function (mapId, map) {
        // Get the map container.
        /** @type {jQuery} */
        var mapWrapper = $('#' + mapId, context).first();
        if (mapWrapper.length && typeof map.googleMap !== 'undefined' && !mapWrapper.hasClass('geolocation-street-view-processed')) {
          // Street View.
          var panorama = map.googleMap.getStreetView();

          // Configure Street View options.
          panorama.setOptions({
            addressControl: map.settings.google_map_settings.addressControl ? true : false,
            enableCloseButton: map.settings.google_map_settings.enableCloseButton ? true : false
          });

          // Open Street View when POV is given.
          if (mapWrapper.is('[data-map-heading][data-map-pitch][data-map-zoom]')) {
            panorama.setPosition(map.googleMap.getCenter());
            panorama.setPov({
              heading: Number(mapWrapper.data('map-heading')),
              pitch: Number(mapWrapper.data('map-pitch')),
              zoom: Number(mapWrapper.data('map-zoom'))
            });
            panorama.setVisible(true);
          }

          // Set the already processed flag.
          map.container.addClass('geolocation-street-view-processed');
        }
      }
    );
  }

})(jQuery, Drupal);
