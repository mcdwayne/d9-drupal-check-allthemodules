(function ($) {

  /**
   * Override the leaflet module's create_feature method to add topojson.
   */
  Drupal.Leaflet.prototype.create_feature = function (feature) {
    var lFeature;
    switch (feature.type) {
      case 'point':
        lFeature = this.create_point(feature);
        break;
      case 'linestring':
        lFeature = this.create_linestring(feature);
        break;
      case 'polygon':
        lFeature = this.create_polygon(feature);
        break;
      case 'multipolygon':
      case 'multipolyline':
        lFeature = this.create_multipoly(feature);
        break;
      case 'json':
        lFeature = this.create_json(feature.json);
        break;
      case 'topojson':
        lFeature = this.create_topojson(feature.json);
        break;
      default:
        return; // Crash and burn.
    }

    // assign our given unique ID, useful for associating nodes
    if (feature.leaflet_id) {
      lFeature._leaflet_id = feature.leaflet_id;
    }

    var options = {};
    if (feature.options) {
      for (var option in feature.options) {
        options[option] = feature.options[option];
      }

      lFeature.setStyle(options);
    }

    return lFeature;
  };

  /**
   * Add a new TopoJSON method to the leaflet object.
   */
  Drupal.Leaflet.prototype.create_topojson = function (json) {
    var lJSON = new L.TopoJSON();
    lJSON.on('featureparse', function (e) {
      e.layer.bindPopup(e.properties.popup);

      for (var layer_id in e.layer._layers) {
        for (var i in e.layer._layers[layer_id]._latlngs) {
          Drupal.Leaflet.bounds.push(e.layer._layers[layer_id]._latlngs[i]);
        }
      }

      if (e.properties.style) {
        e.layer.setStyle(e.properties.style);
      }

      if (e.properties.leaflet_id) {
        e.layer._leaflet_id = e.properties.leaflet_id;
      }
    });

    lJSON.addData(json);
    return lJSON;
  };

})(jQuery);