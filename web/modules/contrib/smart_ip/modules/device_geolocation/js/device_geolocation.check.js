/**
 * @file
 * Device Geolocation module application of "Use AJAX in user's geolocation 
 * checking".
 *
 * Maybe loaded for anonymous and authenticated users, with the Device 
 * Geolocation module enabled.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  /**
   * @namespace
   */
  Drupal.behaviors.deviceGeolocationCheck = {
    attach: function (context) {
      var url = 'device_geolocation/check?geolocate_uri=/' + drupalSettings.path.currentPath;
      $.ajax({
        url: Drupal.url(url),
        type: 'POST',
        dataType: 'json',
        success: function(data) {
          if (data.askGeolocate) {
            drupalSettings.device_geolocation = data.device_geolocation;
            Drupal.behaviors.deviceGeolocationClientSideLocation.attach(context);
          }
        }
      });
    }
  };
})(jQuery, Drupal, drupalSettings);