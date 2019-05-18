(function ($, Drupal) {
  Drupal.behaviors.geolocation2gisBehavior = {
    attach: function (context, settings) {
      $('#map-2gis', context).once('geolocation2gisBehavior').each(function () {
        var map, markers, currentMarker;
        var centerLat = 0, centerLng = 0;
        var hotelLocations = settings.locations;
        if (typeof hotelLocations !== 'undefined' && hotelLocations.length > 0) {
          hotelLocations.forEach(function (item, i, hotelLocations) {
            centerLat += parseFloat(item.lat);
            centerLng += parseFloat(item.lng);
          });

          centerLat = centerLat / hotelLocations.length;
          centerLng = centerLng / hotelLocations.length;

          DG.then(function () {
            map = DG.map('map-2gis', {
              center: [centerLat, centerLng],
              zoom: 13
            });
            map.on('click', function (e) {
              if(typeof currentMarker !== 'undefined')
                currentMarker.removeFrom(map);
              currentMarker = DG.marker([e.latlng.lat, e.latlng.lng]);
              currentMarker.bindPopup(e.latlng.lat + ', ' + e.latlng.lng);
              currentMarker.addTo(map);
              $('#lat-2gis').val(e.latlng.lat);
              $('#lng-2gis').val(e.latlng.lng);
            });

            markers = DG.featureGroup();

            hotelLocations.forEach(function (item, i, hotelLocations) {
              currentMarker = DG.marker([item.lat, item.lng]);
              currentMarker.bindPopup(item.description);
              currentMarker.addTo(map);
              currentMarker.addTo(markers);
              map.fitBounds(markers.getBounds());
              map.zoomOut();
            });
          });
        } else {
          centerLat = 52.27;
          centerLng = 104.21;
          DG.then(function () {
            map = DG.map('map-2gis', {
              center: [centerLat, centerLng],
              zoom: 13
            });
            map.on('click', function (e) {
              if(typeof currentMarker !== 'undefined')
                currentMarker.removeFrom(map);
              currentMarker = DG.marker([e.latlng.lat, e.latlng.lng]);
              currentMarker.bindPopup(e.latlng.lat + ', ' + e.latlng.lng);
              currentMarker.addTo(map);
              $('#lat-2gis').val(e.latlng.lat);
              $('#lng-2gis').val(e.latlng.lng);
            });

          });
        }
      });
    }
  };
})(jQuery, Drupal);
