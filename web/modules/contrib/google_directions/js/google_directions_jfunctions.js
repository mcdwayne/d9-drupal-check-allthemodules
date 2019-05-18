(function ($) {
  'use strict';
  // this function is executed in strict mode

  Drupal.behaviors.google_directions = {
    attach: function (context, settings) {
      var $context = $(context);
      var $source = $context.find('#directions-html').once('get-key');
      // All elements have to exist.
      if (!$source.length) {
        return;
      }
      else {

        // Get Google API key value from drupal settings
        var google_api_key_val = settings.api_key_val;

        // Call Google Maps Javascript Direction Service API
        $.getScript('https://maps.googleapis.com/maps/api/js?key=' + google_api_key_val + '&signed_in=true&libraries=places', function () {
          initAutocomplete();
        });
      }

    }
  };

  $(document).ready(function () {

    /* Prevent form submission on selecting value from autocomplete box of origin & destination */
    $('#edit-origin').keydown(function (e) {
      if (e.which === 13 && $('.pac-container:visible').length) {
        return false;
      }
    });
    $('#edit-destination').keydown(function (e) {
      if (e.which === 13 && $('.pac-container:visible').length) {
        return false;
      }
    });

    $('#edit-getdirection').bind('click', function (event) {
      var time_array;
      var transit_time;
      var request;
      event.preventDefault();
      document.getElementById('directions-html').innerHTML = '';
      var directionsService = new google.maps.DirectionsService();
      var directionsDisplay = new google.maps.DirectionsRenderer();
      directionsDisplay.setPanel(document.getElementById('directions-html'));

      var start = $('#edit-origin').val();
      var end = $('#edit-destination').val();

      var date_string = $('#edit-google-directions-date-date').val();
      var date_array = date_string.split('-');
      var time_string = $('#edit-google-directions-date-time').val();

      if (time_string !== '' && time_string.match(/^(0?[1-9]|1[012])(:[0-5]\d) [APap][mM]$/)) {
        time_array = convert_to_24h(time_string);
      }
      else if (time_string === '') {
        time_array = '00,00';
      }
      else {
        var time_error = Drupal.t('Please enter a valid time.');
        $('#edit-google-directions-date').append('<div class="messages messages--error"><span class="error">' + time_error + '</span></div>');
        return false;
      }

      if (date_string === '' && time_string === '') {
        transit_time = new Date($.now());
      }
      else {
        transit_time = new Date(date_array[0], date_array[1] - 1, date_array[2], time_array[0], time_array[1], '00', '00');
      }
      if ($('#edit-google-directions-transit-time-arrive').is(':checked')) {
        request = {
          origin: start,
          destination: end,
          travelMode: google.maps.TravelMode.TRANSIT,
          provideRouteAlternatives: true,
          transitOptions: {
            arrivalTime: transit_time,
            modes: [google.maps.TransitMode.BUS],
            routingPreference: google.maps.TransitRoutePreference.FEWER_TRANSFERS
          }
        };
      }
      else {
        request = {
          origin: start,
          destination: end,
          travelMode: google.maps.TravelMode.TRANSIT,
          provideRouteAlternatives: true,
          transitOptions: {
            departureTime: transit_time,
            modes: [google.maps.TransitMode.BUS],
            routingPreference: google.maps.TransitRoutePreference.FEWER_TRANSFERS
          }
        };
      }

      directionsService.route(request, function (response, status) {
        if (status === google.maps.DirectionsStatus.OK) {
          for (var i = 0, len = response.routes.length; i < len; i++) {
            if (i > 2) {
              response.routes.splice(i, 1);
            }
          }
          directionsDisplay.setDirections(response);
        }
        else {
          var no_route = Drupal.t('No routes found.');
          $('#directions-html').append('<p>' + no_route + '</p>');
        }
      });
    }); /* End of getDirection click event */

    /* Get current location of user */
    var currgeocoder;
    $('#edit-origin').focus(function () {
      // Set geo location lat and long
      navigator.geolocation.getCurrentPosition(function (position, html5Error) {
        var geo_loc = processGeolocationResult(position);
        var currLatLong = geo_loc.split(',');
        initializeCurrent(currLatLong[0], currLatLong[1]);
      });
    });

    /* Get geo location result */
    function processGeolocationResult(position) {
      var html5Lat = position.coords.latitude; // Get latitude
      var html5Lon = position.coords.longitude; // Get longitude
      return (html5Lat).toFixed(8) + ', ' + (html5Lon).toFixed(8);
    }

    /* Check value is present */
    function initializeCurrent(latcurr, longcurr) {
      currgeocoder = new google.maps.Geocoder();
      if (latcurr !== '' && longcurr !== '') {
        // Call google api function
        var myLatlng = new google.maps.LatLng(latcurr, longcurr);
        return getCurrentAddress(myLatlng);
      }
    }

    /* Get current address */
    function getCurrentAddress(location) {
      currgeocoder.geocode({
        location: location
      }, function (results, status) {
        if (status === google.maps.GeocoderStatus.OK) {
          $('#edit-origin').val(results[0].formatted_address);
        }
      });
    }

    /* Swap text between 'From' and 'To' */
    $('#googledirectionsform a#edit-swap').bind('click', function (event) {
      var origin = $('#edit-origin').val();
      var destination = $('#edit-destination').val();
      $('#edit-origin').val(destination);
      $('#edit-destination').val(origin);
    });

  });
})(jQuery);

// This example displays an address form, using the autocomplete feature
// of the Google Places API to help users fill in the information.

function initAutocomplete() {
  'use strict';
  // this function is executed in strict mode

  // Create the autocomplete object, restricting the search to geographical
  // location types.
  var source_autocomplete = new google.maps.places.Autocomplete(
          /** @type {!HTMLInputElement} */(document.getElementById('edit-origin')),
          {types: ['geocode']});

  var destination_autocomplete = new google.maps.places.Autocomplete(
          /** @type {!HTMLInputElement} */(document.getElementById('edit-destination')),
          {types: ['geocode']});
  google.maps.event.addListener(source_autocomplete, 'place_changed', function () { });
  google.maps.event.addListener(destination_autocomplete, 'place_changed', function () { });
}

/* Function to convert 12hrs time format to 24hrs time format*/
function convert_to_24h(time_str) {
  'use strict';
  // this function is executed in strict mode

  if (time_str === null) {
    time_str = '00:00';
  }
  // Convert a string like 10:05:23 PM to 24h format, returns like [22,5,23]
  var time = time_str.match(/(\d+):(\d+) (\w)/);

  var hours = Number(time[1]);
  var minutes = Number(time[2]);
  var meridian = time[3].toLowerCase();

  if (meridian === 'p' && hours < 12) {
    hours = hours + 12;
  }
  else if (meridian === 'a' && hours === 12) {
    hours = hours - 12;
  }
  return [hours, minutes];
}
