(function ($) {

  /**
   * Bind a function to the post add features trigger.
   *
   * This function creates a superBounds LatLngBounds object.
   * This object is populated by the accumulated bounds from
   * all added features. This object is then used to pass to
   * the fitBounds leaflet method which zooms and centres the
   * map to cover all the features.
   */
  $(document).bind('leaflet.features', function (e, exist, leaflet) {
    var superBounds = new L.LatLngBounds();
    var map = leaflet.lMap;

    // Iterated over all the layers in the map.
    for (var l in map._layers) {
      var overlay = map._layers[l];
      if (overlay._layers) {
        // Iterate over all the features.
        for (var f in overlay._layers) {
          var feature = overlay._layers[f];
          var bounds;
          if (feature.getBounds) {
            bounds = feature.getBounds();
          }
          else if (feature._latlng) {
            bounds = L.latLngBounds(feature._latlng, feature._latlng);
          }
          // Extend the superBounds with our feature's bounds.
          if (bounds) {
            superBounds.extend(bounds);
          }
        }
      }
    }
    // Fit the map to the superBounds.
    leaflet.lMap.fitBounds(superBounds);
  });

  /**
   * Bind a function to the post add feature trigger.
   *
   * Used to add an optional text label to a country feature.
   */
  $(document).bind('leaflet.feature', function (Event, lFeature, feature, leaflet) {
    if (feature.label) {
      var map = leaflet.lMap;
      var centre = lFeature.getBounds().getCenter();
      var code = feature.code;
      // Use the code to match against a drupalSettings item to
      // fetch the latlng array.
      // @see leaflet_countries_views_pre_render().
      if (typeof drupalSettings.leaflet_countries[code] != 'undefined') {
        centre = drupalSettings.leaflet_countries[code];
      }
      // Add a marker using the DivIcon type so we can specify
      // some custom HTML to be used as the label.
      var marker = L.marker(centre, {
        icon: new L.DivIcon({
          className: 'leaflet-map__label',
          html: feature.label,
          iconSize: 74,
          iconAnchor: [37, 10],
          popupAnchor: [0, -30]
        }),
      });
      if (feature.popup && feature.labelTriggerPopup) {
        marker.bindPopup(feature.popup);
      }
      marker.addTo(map);
    }
  });

})(jQuery);