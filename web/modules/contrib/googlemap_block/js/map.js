/**
 * @file
 */

(function ($) {

  'use strict';

  Drupal.behaviors.googlemap_block = {
    attach: function (context, settings) {

      var zoom_level = settings.map_zoom_level;
      var google_api_key = settings.api_key;
      var latitude = settings.lat;
      var longitude = settings.long;
      var addressData = JSON.stringify(settings.all_address);
      // Parse JSON.
      var json_obj = $.parseJSON(addressData);
      var locations = [];
      for (var i in json_obj) {
        if (i) {
          var arr = json_obj[i].location_name + json_obj[i].location_address;
          locations.push([arr, json_obj[i].lat, json_obj[i].long]);
        }
      }
      $.getScript('https://maps.googleapis.com/maps/api/js?key=' + google_api_key, function () {
        var map = new google.maps.Map(document.getElementById('map'), {
          zoom: parseInt(zoom_level),
          center: new google.maps.LatLng(latitude, longitude),
          minZoom: 2,
          mapTypeId: google.maps.MapTypeId.ROADMAP
        });

        var infowindow = new google.maps.InfoWindow();
        var marker;
        var j;
        for (j = 0; j < locations.length; j++) {
          marker = new google.maps.Marker({
            position: new google.maps.LatLng(locations[j][1], locations[j][2]),
            map: map
          });
          google.maps.event.addListener(marker, 'click', (function (marker, j) {
            return function () {
              infowindow.setContent(locations[j][0]);
              infowindow.open(map, marker);
            };
          })(marker, j));
        }
      });
    }
  };
})(jQuery);
