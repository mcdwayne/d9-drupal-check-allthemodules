if (!google.maps.Polygon.prototype.getBounds) {
  /**
   * @returns {google.maps.LatLngBounds}
   */
  google.maps.Polygon.prototype.getBounds = function() {
    var bounds = new google.maps.LatLngBounds();

    this.getPath().forEach(function(latLng) {
      bounds.extend(latLng);
    });

    return bounds;
  };
}
