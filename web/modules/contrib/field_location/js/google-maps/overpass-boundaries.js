/**
 * @constructor
 *
 * @param {google.maps.LatLngBounds} bounds
 *
 * @returns {jQuery.Deferred}
 */
function OverpassBoundaries(bounds) {
  if (!(bounds instanceof google.maps.LatLngBounds)) {
    throw new TypeError();
  }

  var deferred = jQuery.Deferred();

  // @link http://lxbarth.com/bbox
  jQuery.getJSON('https://www.overpass-api.de/api/interpreter?data=[out:json];node(' + OverpassBoundaries.boundingBox(bounds) + ');out;', function(response) {
    response.elements.pop();

    var paths = [];

    jQuery.each(response.elements, function() {
      if (this.lat && this.lon) {
        paths.push({
          lat: this.lat,
          lng: this.lon
        });
      }
    });

    deferred.resolve(paths);
  });

  return deferred;
}
/**
 * @link http://wiki.openstreetmap.org/wiki/Bounding_Box
 *
 * @param {google.maps.LatLngBounds} bounds
 *
 * @returns {String}
 */
OverpassBoundaries.boundingBox = function(bounds) {
  var southwest = bounds.getSouthWest();
  var northeast = bounds.getNorthEast();
  var southwestLatitude = southwest.lat();
  var southwestLongitude = southwest.lng();
  var northeastLatitude = northeast.lat();
  var northeastLongitude = northeast.lng();

  return [
    Math.min.apply(Math, [southwestLatitude, northeastLatitude]),
    Math.min.apply(Math, [southwestLongitude, northeastLongitude]),
    Math.max.apply(Math, [southwestLatitude, northeastLatitude]),
    Math.max.apply(Math, [southwestLongitude, northeastLongitude])
  ].join(',');
};
