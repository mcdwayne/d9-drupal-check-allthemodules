/**
 * @constructor
 *
 * @param {google.maps.Polygon} polygon
 * @param {jQuery} $storage
 */
function PolygonCoordinates(polygon, $storage) {
  if (!(polygon instanceof google.maps.Polygon) || !($storage instanceof jQuery) || $storage.length < 1) {
    throw new TypeError();
  }

  this.polygon = polygon;
  this.field = $storage;

  try {
    this.data = JSON.parse(this.field.val());
  }
  catch (e) {
    this.data = [];
  }

  if (this.data.length > 0) {
    polygon.setPath(this.data);
    polygon.getMap().fitBounds(polygon.getBounds());
  }
}

PolygonCoordinates.prototype = {
  constructor: PolygonCoordinates,
  /**
   * @param {Object[]} data
   *
   * @returns {PolygonCoordinates}
   */
  set: function(data) {
    this.data = data;
    this.field.val(JSON.stringify(this.data));

    return this;
  },
  /**
   * @returns {PolygonCoordinates}
   */
  update: function() {
    var data = [];

    this.polygon.getPath().forEach(function(latLng) {
      data.push({
        lat: latLng.lat(),
        lng: latLng.lng()
      });
    });

    return this.set(data);
  }
};
