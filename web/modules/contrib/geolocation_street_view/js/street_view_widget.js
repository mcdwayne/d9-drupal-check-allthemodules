/**
 * @file
 * Geolocation Street View widget Javascript.
 */

(function ($, Drupal) {
  'use strict';

  Drupal.geolocation.streetViewWidget = Drupal.geolocation.streetViewWidget || {};

  /**
   * Attach Google Street View widget functionality.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches Google Street View widget functionality to relevant elements.
   */
  Drupal.behaviors.geolocationStreetViewFormatter = {
    attach: function (context, drupalSettings) {
      if (typeof Drupal.geolocation.loadGoogle === 'function') {
        // First load the library from google.
        Drupal.geolocation.loadGoogle(function () {
          initialize(drupalSettings.geolocation.widgetMaps, context);
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

          // POV field values.
          var heading = $('.canvas-' + mapId + ' .geolocation-hidden-heading').attr('value');
          var pitch = $('.canvas-' + mapId + ' .geolocation-hidden-pitch').attr('value');
          var zoom = $('.canvas-' + mapId + ' .geolocation-hidden-zoom').attr('value');

          // Open Street View when POV is given.
          if (!isNaN(parseFloat(heading)) && !isNaN(parseFloat(pitch)) && !isNaN(parseFloat(zoom))) {
            panorama.setPosition(map.googleMap.getCenter());
            panorama.setPov({
              heading: parseFloat(heading),
              pitch: parseFloat(pitch),
              zoom: parseFloat(zoom)
            });
            panorama.setVisible(true);
          }

          // Add the position and POV listeners.
          panorama.addListener('position_changed', function () {
            Drupal.geolocation.geocoderWidget.setHiddenInputFields(panorama.getPosition(), map);
          });
          panorama.addListener('pov_changed', function () {
            Drupal.geolocation.streetViewWidget.setHiddenInputFields(panorama.getPov(), map);
          });

          // Clear POV fields when location is changed or cleared.
          Drupal.geolocation.geocoderWidget.addLocationCallback(function (location) {
            Drupal.geolocation.streetViewWidget.clearHiddenInputFields(map);
          }, mapId);
          Drupal.geolocation.geocoderWidget.addClearCallback(function () {
            Drupal.geolocation.streetViewWidget.clearHiddenInputFields(map);
          }, mapId);

          // Set the already processed flag.
          map.container.addClass('geolocation-street-view-processed');
        }
      }
    );
  }

  /**
   * Set the Street View input fields.
   *
   * @param {StreetViewPov} pov - The POV from Street View.
   * @param {GeolocationMap} map - The settings object that contains all of the necessary metadata for this map.
   */
  Drupal.geolocation.streetViewWidget.setHiddenInputFields = function (pov, map) {
    $('.canvas-' + map.id + ' .geolocation-hidden-heading').attr('value', pov.heading);
    $('.canvas-' + map.id + ' .geolocation-hidden-pitch').attr('value', pov.pitch);
    $('.canvas-' + map.id + ' .geolocation-hidden-zoom').attr('value', pov.zoom);
  };

  /**
   * Clear the Street View input fields.
   *
   * @param {GeolocationMap} map - The settings object that contains all of the necessary metadata for this map.
   */
  Drupal.geolocation.streetViewWidget.clearHiddenInputFields = function (map) {
    $('.canvas-' + map.id + ' .geolocation-hidden-heading').attr('value', '');
    $('.canvas-' + map.id + ' .geolocation-hidden-pitch').attr('value', '');
    $('.canvas-' + map.id + ' .geolocation-hidden-zoom').attr('value', '');
  };

})(jQuery, Drupal);
