/**
 * @file
 * Defines Javascript behaviors for the auto block refresh module.
 */

(function ($) {
  Drupal.behaviors.trackSiteVisitor = {
    attach: function () {

      setInterval(function() {
        if (navigator.geolocation) {
          var timeoutVal = 10 * 1000 * 1000;
          navigator.geolocation.getCurrentPosition(
            getPosition,
            getDisplayError,
            { enableHighAccuracy: true, timeout: timeoutVal, maximumAge: 0 }
          );
        }
        function getPosition(position) {
          var latitude = position.coords.latitude;
          var longitude = position.coords.longitude;
          var request = new XMLHttpRequest();
          var method = 'GET';
          var url = 'http://maps.googleapis.com/maps/api/geocode/json?latlng='+latitude+','+longitude+'&sensor=true';
          var async = true;
          request.open(method, url, async);
          request.onreadystatechange = function() {
            if (request.readyState === 4 && request.status === 200) {
              var data = JSON.parse(request.responseText);
              var addressComponents = data.results[0].address_components;
              var trackedData = {};
              for (var i = 0; i < addressComponents.length; i++) {
                var types = addressComponents[i].types;
                trackedData[types] = addressComponents[i].long_name;
              }
              // trackedData.toSource();
              jQuery( ".tsv-current-location" ).replaceWith( "<div class='tsv-current-location'>"+trackedData.toSource()+"</div>" );
            }
          };
          request.send();
        }
        function getDisplayError(error) {
          var errors = {
            1: 'Permission denied',
            2: 'Position unavailable',
            3: 'Request timeout'
          };
        }
      }, 10000);

      /*
      // get the current location
      if (navigator.geolocation) {
        var timeoutVal = 10 * 1000 * 1000;
        navigator.geolocation.getCurrentPosition(
          savePosition,
          displayError,
          { enableHighAccuracy: true, timeout: timeoutVal, maximumAge: 0 }
        );
      }
      else {
        // alert("Geolocation is not supported by this browser");
      }
      function savePosition(position) {
        var latitude = position.coords.latitude;
        var longitude = position.coords.longitude;
        var request = new XMLHttpRequest();
        var method = 'GET';
        var url = 'http://maps.googleapis.com/maps/api/geocode/json?latlng='+latitude+','+longitude+'&sensor=true';
        var async = true;
        request.open(method, url, async);
        request.onreadystatechange = function() {
          if (request.readyState === 4 && request.status === 200) {
            var data = JSON.parse(request.responseText);
            var addressComponents = data.results[0].address_components;
            var trackedData = {};
            for (var i = 0; i < addressComponents.length; i++) {
              var types = addressComponents[i].types;
              trackedData[types] = addressComponents[i].long_name;
            }
            $.post(Drupal.url('track-site-visitor/11/22'), trackedData);
          }
        };
        request.send();
      }
      function displayError(error) {
        var errors = {
          1: 'Permission denied',
          2: 'Position unavailable',
          3: 'Request timeout'
        };
      }*/

    }
  };
})(jQuery);