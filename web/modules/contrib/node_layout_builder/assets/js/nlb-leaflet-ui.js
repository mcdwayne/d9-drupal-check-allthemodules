/**
 * @file
 * File  nlb-leaflet-ui.js.
 *
 * Map element with Leaflet.
 */

(function ($, Drupal, drupalSettings) {

    Drupal.behaviors.builder_map = {
      attach: function (context, settings) {
        init_leaflet_map(context);
      }
    };

    function init_leaflet_map(context) {
      $('#nlb-map', context).each(function () {
        let zoom = 6;
        let lat = 48.89787934181869;
        let lon = 2.3526754975318913;

        if ($('#nlb-map').attr('data-options') != '') {
          const options = $.parseJSON($('#nlb-map').attr('data-options'));
          zoom = parseInt(options.zoom);
          lat = parseFloat(options.lat);
          lon = parseFloat(options.lon);
        }

        let nlbMap = L.map('nlb-map').setView([lat, lon], zoom);

        L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token=pk.eyJ1IjoibWFwYm94IiwiYSI6ImNpejY4NXVycTA2emYycXBndHRqcmZ3N3gifQ.rJcFIG214AriISLbB6B5aw', {
          maxZoom: 18,
          id: 'mapbox.streets'
        })
          .addTo(nlbMap);

        var marker = L.marker([lat, lon],
          { draggable: true }
        );

        // Handler to store new gps after drag marker.
        marker.on('dragend', function (event) {
          var marker = event.target;
          var location = marker.getLatLng();
          $('input[name="configue[settings][map_gps_latitude]"]').val(location.lat);
          $('input[name="configue[settings][map_gps_longitude]"]').val(location.lng);
        });
        marker.addTo(nlbMap);

        // Change zoom.
        $('input[name="configue[settings][map_gps_zoom]"]').change(function () {
          zoom = parseInt($(this).val());
          nlbMap.setZoom(zoom);
        });
      })
    }

})(jQuery, Drupal, drupalSettings);
