(function ($, Drupal) {
  Drupal.behaviors.geolocation2gisBehavior = {
    attach: function (context, settings) {
      $('#map-2gis', context).once('geolocation2gisBehavior').each(function () {
        var map;
        var centerLat = 0, centerLng = 0;
        var hotelLocations = settings.locations;
        if(typeof hotelLocations !== 'undefined' && hotelLocations.length > 0) {
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

            var markers = DG.featureGroup();

            hotelLocations.forEach(function (item, i, hotelLocations) {
              DG.marker([item.lat, item.lng]).addTo(markers);
              DG.marker([item.lat, item.lng]).addTo(map).bindPopup(item.description);
              map.fitBounds(markers.getBounds());
              map.zoomOut();
            });
          });
        }else{
          centerLat = 52.27;
          centerLng = 104.21;
          DG.then(function () {
            map = DG.map('map-2gis', {
              center: [centerLat, centerLng],
              zoom: 13
            });
          });
        }
      });
    }
  };

})(jQuery, Drupal);
