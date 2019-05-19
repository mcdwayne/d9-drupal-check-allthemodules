/**
 * @file
 * Contains the definition of HTML5 Geolocation.
 */

  /**
   * Add Javascript when DOM is ready.
   */
jQuery(document).ready(function () {
  'use strict';
  var latitude = getCookie('latitude');
  var longitude = getCookie('longitude');
  if ((typeof latitude === 'undefined' && typeof longitude === 'undefined') || (latitude === '' && longitude === '')) {
    // Show HTML5 Geolocation Popup.
    if (navigator && navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(geo_success, geo_error);
    }
  }

});

/**
 * Get Lat/Lng Cookie Value.
 *
 * @param {Object} cname
 *   Cookie Name.
 *
 * @return {Object}
 *   Cookie Value.
 */
function getCookie(cname) {
  'use strict';
  var name = cname + '=';
  var decodedCookie = decodeURIComponent(document.cookie);
  var ca = decodedCookie.split(';');
  for (var i = 0; i < ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0) === ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) === 0) {
      return c.substring(name.length, c.length);
    }
  }
  return '';
}

/**
 * Create Cookie with Lat/Long.
 *
 * @param {String} name
 *   Cookie Name.
 * @param {Float} value
 *   Feocodes Data.
 * @param {Int} days
 *   Number of days cookie is alive.
 */
function createCookie(name, value, days) {
  'use strict';
  var date = new Date();
  date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
  var expires = '; expires=' + date.toGMTString();
  document.cookie = name + '=' + value + expires;
}

/**
 * Action when click on Block button.
 *
 * @param {String} error
 *   Error Value.
 */
function geo_error(error) {
  'use strict';
  // Error Handler
}

/**
 * Action when click on Allow button.
 *
 * @param {Object} position
 *   Geocodes Data.
 */
function geo_success(position) {
  'use strict';
  var lat = position.coords.latitude;
  var lng = position.coords.longitude;
  createCookie('latitude', lat, '7');
  createCookie('longitude', lng, '7');
}
