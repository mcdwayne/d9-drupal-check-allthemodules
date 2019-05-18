(function ($) {

  Drupal.behaviors.leaflet_maptiler = {
    attach:function (context, settings) {

      $.each(settings.leaflet, function (m, data) {
        /*
         * If the map already exists.
         */
        var container = L.DomUtil.get(this.mapId);
        if (!container || container._leaflet_id) {
          /*
           * Initializes map as null.
           */
          var map = null;
          /*
           * Gets the map object.
           */
          if (typeof data.lMap !== 'undefined' && data.lMap !== null) {
            map = data.lMap;
            /*
             * Sets the map default zoom.
             */
            if (typeof this.map.settings.zoom !== 'undefined' && this.map.settings.zoom !== null) {
              map.setZoom(this.map.settings.zoom);
            }
            else {
              map.setZoom(this.map.settings.zoomDefault);
            }
          }

          /*
           * If a map has been found.
           */
          if (map !== null) {
            /*
             * If Geocoder plugin is set.
             */
            if (typeof L.Control.Geocoder !== 'undefined' && L.Control.Geocoder !== null) {
              /*
               * Initializes the Leaflet Control Geocoder plugin.
               */
              var geocodeService = new L.Control.Geocoder.Nominatim({reverseQueryParams: {"accept-language": settings.leaflet_maptiler.language}});
              /*
               * Iterates over the layers of the map in order to
               * set the popup text for every marker.
               */
              map.eachLayer(function (layer) {
                /*
                 * If the layer is has latitude and longitude values.
                 */
                if (typeof layer._latlng !== 'undefined' && layer._latlng !== null) {
                  geocodeService.reverse(layer._latlng, 1, function(results) {
                    /*
                     * Gets the results from geocoder.
                     */
                    var r = results[0];
                    if (r) {
                      /*
                       * Sets the text for the popup.
                       */
                      layer.bindPopup(r.name || r.html);
                    }
                  });
                }
              });
            }
          }
        }
      });
    }
  };

})(jQuery);
