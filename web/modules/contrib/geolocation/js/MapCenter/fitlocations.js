(function ($, Drupal) {

  'use strict';

  Drupal.geolocation = Drupal.geolocation || {};
  Drupal.geolocation.mapCenter = Drupal.geolocation.mapCenter || {};

  /**
   * @param centerOption.settings.reset_zoom {Boolean}
   */
  Drupal.geolocation.mapCenter.fit_bounds = function(map, centerOption) {
    map.fitMapToMarkers();

    if (centerOption.settings.reset_zoom) {
      map.setZoom();
    }

    return false;
  }

})(jQuery, Drupal);
