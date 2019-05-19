/**
 * @file
 * JavaScript core for the Device Geolocation module.
 *
 * Maybe loaded for anonymous and authenticated users, with the Device
 * Geolocation module enabled.
 */

(function ($, Drupal, drupalSettings) {
  /**
   * @namespace
   */
  Drupal.behaviors.deviceGeolocationClientSideLocation = {
    attach: function (context) {
      var geolocationSource = drupalSettings.smartIpSrc.geocodedSmartIp;
      var longitude = null;
      var latitude  = null;
      if (!drupalSettings.device_geolocation.askGeolocate) {
        // Don't ask user for geolocation. Duration of frequency checking is set.
        return;
      }
      drupalSettings.device_geolocation.askGeolocate = false;
      if (isset(drupalSettings.device_geolocation.longitude)) {
        longitude = !isNaN(drupalSettings.device_geolocation.longitude) ? drupalSettings.device_geolocation.longitude : (!isNaN(drupalSettings.device_geolocation.longitude[0]) ? drupalSettings.device_geolocation.longitude[0] : null);
      }
      if (isset(drupalSettings.device_geolocation.latitude)) {
        latitude = !isNaN(drupalSettings.device_geolocation.latitude) ? drupalSettings.device_geolocation.latitude : (!isNaN(drupalSettings.device_geolocation.latitude[0]) ? drupalSettings.device_geolocation.latitude[0] : null);
      }
      // Try W3C Geolocation (Preferred) to detect user's location.
      if (navigator.geolocation && !drupalSettings.device_geolocation.debugMode) {
        navigator.geolocation.getCurrentPosition(function (position) {
          geolocationSource = drupalSettings.smartIpSrc.w3c;
          geocoderSendAddress(position.coords.latitude, position.coords.longitude);
        }, function () {
          // Smart IP fallback.
          geocoderSendAddress(latitude, longitude);
        });
      }
      // Smart IP fallback or using debug mode coordinates.
      else {
        geocoderSendAddress(latitude, longitude);
      }
      /**
       * Possible array items:
       * -street_number;
       * -postal_code;
       * -route;
       * -neighborhood;
       * -locality;
       * -sublocality;
       * -establishment;
       * -administrative_area_level_N;
       * -country;
       */
      function geocoderSendAddress(latitude, longitude) {
        if (latitude != null && longitude != null && !isNaN(latitude) && !isNaN(longitude)) {
          var geocoder = new google.maps.Geocoder();
          var latlng = new google.maps.LatLng(latitude, longitude);
          var address = new Object;
          geocoder.geocode({'latLng': latlng}, function (results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
              if (results[0]) {
                for (var i = 0; i < results[0].address_components.length; ++i) {
                  var long_name = results[0].address_components[i].long_name || '';
                  var short_name = results[0].address_components[i].short_name || '';
                  var type = results[0].address_components[i].types[0];
                  if (long_name != null) {
                    // Manipulate the result.
                    switch (type) {
                      case 'country':
                        address['country'] = long_name;
                        if (short_name != null) {
                          address['countryCode'] = short_name;
                        }
                        break;
                      case 'locality':
                        address[type]   = long_name;
                        address['city'] = long_name;
                        break;
                      case 'postal_code':
                        address[type]  = long_name;
                        address['zip'] = long_name;
                        break;
                      case 'administrative_area_level_1':
                        address[type]     = long_name;
                        address['region'] = long_name;
                        if (short_name != null) {
                          address['regionCode'] = short_name;
                        }
                        break;
                      default:
                        address[type] = long_name;
                    }
                  }
                }
                address['source'] = geolocationSource;
                address['latitude']  = latitude;
                address['longitude'] = longitude;
                $.ajax({
                  url: Drupal.url('device_geolocation/client_side_location'),
                  type: 'POST',
                  dataType: 'json',
                  data: address
                });
              }
            }
            else {
              $.ajax({
                url: Drupal.url('device_geolocation/client_side_location'),
                type: 'POST',
                dataType: 'json',
                data: ({
                  latitude: latitude,
                  longitude: longitude
                })
              });
              if (window.console) {
                console.log('Geocoder failed due to: ' + status);
              }
            }
          });
        }
      }
      function isset() {
        var a = arguments;
        var l = a.length, i = 0;

        if (l === 0) {
          throw new Error('Empty');
        }
        while (i !== l) {
          if (typeof(a[i]) == 'undefined' || a[i] === null) {
            return false;
          }
          else {
            i++;
          }
        }
        return true;
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
