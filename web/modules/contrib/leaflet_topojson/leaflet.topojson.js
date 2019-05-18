(function ($) {

  /**
   * Extend leaflet so it understands TopoJSON.
   *
   * @see http://blog.webkid.io/maps-with-leaflet-and-topojson/
   */
  L.TopoJSON = L.GeoJSON.extend({
    addData: function(jsonData) {
      if (jsonData.type === "Topology") {
        for (key in jsonData.objects) {
          geojson = topojson.feature(jsonData, jsonData.objects[key]);
          L.GeoJSON.prototype.addData.call(this, geojson);
        }
      }
      else {
        L.GeoJSON.prototype.addData.call(this, jsonData);
      }
    }
  });

})(jQuery);