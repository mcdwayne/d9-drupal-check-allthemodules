(function ($, Drupal, L) {
  Drupal.visualnData.drawers.visualnLeafletMapBasicDrawer = function(drawings, vuid) {
    var drawing = drawings[vuid];

    var data = drawing.resource.data;

    var html_selector = drawing.html_selector;

    var center_lat = drawing.drawer.config.center_lat;
    var center_lon = drawing.drawer.config.center_lon;
    // set center as a midpoint of all points (if not empty)
    var calculate_center = drawing.drawer.config.calculate_center;
    var map_height = drawing.drawer.config.map_height;

    var locations = data;


    // @todo: get default zoom from settings
    var zoom = 8;



    var leaflet_map_id = html_selector + '--leaflet-map';
    // @todo: use wrapper class instead
    $('.' + html_selector).append('<div id="' + leaflet_map_id + '" class="visualn-leaflet-map-basic-map"></div>');
    if (map_height) {
      $('.' + html_selector).find('.visualn-leaflet-map-basic-map').css('height', map_height);
    }

    if (locations.length && locations.length == 1) {
      var map = L.map(leaflet_map_id).setView([locations[0].lat,locations[0].lon], zoom);
    }
    else if (calculate_center && locations.length) {
      var points = [];
      for (var i = 0; i < locations.length; i++) {
        points.push([locations[i].lat,locations[i].lon]);
      }
      var bounds = new L.LatLngBounds(points);
      var centerLatLon = bounds.getCenter();
      var map = L.map(leaflet_map_id).setView([centerLatLon.lat, centerLatLon.lng], zoom);
      map.fitBounds(bounds);
    }
    else {
      var map = L.map(leaflet_map_id).setView([center_lat, center_lon], zoom);
    }


    // @todo: set provider in drawer js settings
    L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // add markers to the map
    for (var i = 0; i < locations.length; i++) {
      marker = new L.marker([locations[i].lat,locations[i].lon])
        .bindPopup(locations[i].title)
        .addTo(map);
    }

  };
})(jQuery, Drupal, L);
