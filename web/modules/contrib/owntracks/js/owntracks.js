(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.owntracks = {
    attach: function (context) {
      $('.owntracks-map').once('owntracks-map').each(function () {

        /* global L */
        var id = $(this).attr('id');
        var map = L.map(id);
        var track = drupalSettings.owntracks.track[id];

        L.tileLayer(drupalSettings.owntracks.map.tileLayerUrl, {
          attribution: drupalSettings.owntracks.map.tileLayerAttribution
        }).addTo(map);

        if (track !== null) {
          var polyline = L.polyline(track, {color: drupalSettings.owntracks.map.polylineColor}).addTo(map);
          map.fitBounds(polyline.getBounds());

          $.each(track, function (i, e) {
            L.marker(e).addTo(map);
          });
        }
        else {
          map.setView([51.4833333, 7.2166667], 2);
        }
      });
    }
  };

}(jQuery, Drupal, drupalSettings));
