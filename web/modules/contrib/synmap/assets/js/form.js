/**
 * @file
 * Author: Synapse-studio.
 */

(function ($) {
  $(document).ready(function () {

    // Map stuff.
    ymaps.ready(ymapsInit);

    function ymapsInit() {
      $('#edit-geo').after('<div id="map-picker" style="min-height:300px"></div>');

      // MAP create.
      var mapSet = drupalSettings.synmap.data;
      var map = new ymaps.Map('map-picker', {
        center: [mapSet.longitude, mapSet.latitude],
        zoom: mapSet.zoom,
        controls: ['zoomControl', 'fullscreenControl']
      });
      var searchControl = new ymaps.control.SearchControl({
        options: {
          provider: 'yandex#search'
        }
      });
      map.controls.add(searchControl);
      map.behaviors.disable('scrollZoom');

      // Point options.
      var defaultParams = {
        geometry: {
          type: 'Point',
          coordinates: [mapSet.longitude, mapSet.latitude]
        },
        properties: {
          iconContent: mapSet.name
        }
      };
      var defaultPreset = {
        preset: 'islands#blackStretchyIcon',
        draggable: true
      };

      // Add Placemark.
      var companyPlacemark = new ymaps.GeoObject(defaultParams, defaultPreset);
      map.geoObjects.add(companyPlacemark);
      // Drag Placemark event.
      companyPlacemark.events.add('dragend', function (e) {
        writeCoords(companyPlacemark);
      });
      // Map click event > change Placemark coords.
      map.events.add('click', function (e) {
        var coords = e.get('coords');
        companyPlacemark.geometry.setCoordinates(coords);
        writeCoords(companyPlacemark);
      });
      // Coords 2 formFields.
      function writeCoords (placemark) {
        var coords = placemark.geometry.getCoordinates();
        $(mapSet.editlatitude).attr('value', coords[1]);
        $(mapSet.editlongitude).attr('value', coords[0]);
      }
    }
  });
})(this.jQuery);
