(function($, name) {
  'use strict';

  $.each(drupalSettings[name], function(fieldName, settings) {
    $('.field-location.' + fieldName).each(function(i) {
      var options = settings[i];
      var $container = $(this);
      var $search = $container.find('.search').val(options.locality);
      var search = new google.maps.places.Autocomplete($search[0]);
      var map = new google.maps.Map($container.find('.map')[0], {
        // Disable switching between road map, landscape and other types.
        mapTypeControl: false,
        // Disable zooming on scrolling mouse wheel.
        scrollwheel: false,
        zoom: 4,
        // This is a geographical center of Earth.
        // @see https://en.wikipedia.org/wiki/Geographical_centre_of_Earth
        center: {
          lat: 39,
          lng: 34
        }
      });
      var polygon = new google.maps.Polygon({
        strokeOpacity: 0.5,
        strokeWeight: 2,
        fillOpacity: 0.1,
        strokeColor: '#FF0000',
        fillColor: '#FF0000',
        editable: true,
        map: map
      });
      var coordinates = new PolygonCoordinates(polygon, $container.find('.coordinates'));

      // Add "Find me" button.
      $('<div class="location-button">').each(function() {
        $(this).on('click', function(event) {
          event.preventDefault();

          var $element = $(this).addClass('active');

          navigator.geolocation.getCurrentPosition(function(position) {
            var me = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);

            new google.maps.Marker({
              position: me,
              map: map
            });

            map.setCenter(me);
            $element.removeClass('active');
          });
        });

        map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(this);
      });

      // Read polygon points before form submission.
      $container.closest('form').on('submit', function(event) {
        event.preventDefault();
        coordinates.update();

        $(this).off(event.type).trigger(event.type);
      });

      // Prevent form submit when "return" button clicked.
      $search.on('keydown', function(event) {
        if (13 === event.which) {
          event.preventDefault();
        }
      });

      // Handle places autocomplete.
      search.addListener('place_changed', function() {
        var place = search.getPlace();

        $container.find('.locality').val($search.val());

        if (place.hasOwnProperty('geometry')) {
          var bounds = new google.maps.Circle({center: place.geometry.location, radius: 250}).getBounds();

          // new OverpassBoundaries(bounds).then(paths => polygon.setPaths(paths));

          polygon.setPath([bounds.getNorthEast(), bounds.getSouthWest()]);
          map.fitBounds(bounds);
        }
      });
    });
  });
})(jQuery, 'locationFields');
