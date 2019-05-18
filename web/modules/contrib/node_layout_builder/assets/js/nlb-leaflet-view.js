/**
 * @file
 * File nlb-leaflet-view.js.
 *
 * Map element with Leaflet.
 */

(function ($, Drupal) {

    Drupal.behaviors.builder_map = {
      attach: function (context, settings) {
        init_leaflet_map_view(context);
      }
    };

    function init_leaflet_map_view(context) {
      $('.nlb-map-view', context).each(function () {
        const myID = $(this).attr('id');
        const options = $.parseJSON($('#' + myID).attr('data-options'));
        const zoom = parseInt(options.zoom);
        const lat = parseFloat(options.lat);
        const lon = parseFloat(options.lon);

        let nlbMapView = L.map(myID, { zoomControl:true }).setView([lat, lon], parseInt(zoom));

        L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token=pk.eyJ1IjoibWFwYm94IiwiYSI6ImNpejY4NXVycTA2emYycXBndHRqcmZ3N3gifQ.rJcFIG214AriISLbB6B5aw', {
          maxZoom: 18,
          id: 'mapbox.streets'
        })
          .addTo(nlbMapView);

        const marker = L.marker([lat, lon]);
        marker.addTo(nlbMapView);
      })
    }

})(jQuery, Drupal);
