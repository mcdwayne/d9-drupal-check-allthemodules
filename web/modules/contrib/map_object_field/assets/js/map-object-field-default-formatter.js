/**
 * @file
 */

(function ($, Drupal, Backbone) {
  'use strict';
  // Start app from Drupal behaviour.
  Drupal.behaviors.map_object_field_default_formatter = {
    attach: function (context) {
      $('.map-object-field .map', context).each(function (index, item) {
        var lat = $(this).attr('data-center-lat');
        var lng = $(this).attr('data-center-lng');
        var zoom = parseInt($(this).attr('data-zoom'));
        var mapType = $(this).attr('data-map-type');

        var latlng = new google.maps.LatLng(lat, lng);
        var mapOptions = {
          zoom: zoom,
          center: latlng,
          streetViewControl: false,
          mapTypeId: google.maps.MapTypeId[mapType.toUpperCase()]
        };
        var map = new google.maps.Map(this, mapOptions);

        try {
          var overlays = $.parseJSON($(this).attr('data-overlays'));
          for (var i = 0; i < overlays.length; i++) {
            var overlay = overlays[i];
            if ((typeof overlay.coordinates != 'undefined') && (overlay.coordinates.length > 0)) {
              var overlayObject = '';
              var mapParams = {
                map: map
              };
              switch (overlay.type) {
                case 'marker':
                  mapParams.position = new google.maps.LatLng(overlay.coordinates[0].lat, overlay.coordinates[0].lng);
                  if (typeof overlay.extraParams.icon != 'undefined') {
                    mapParams.icon = overlay.extraParams.icon;
                  }
                  overlayObject = new google.maps.Marker(mapParams);
                  break;

                case 'circle':
                  mapParams.center = new google.maps.LatLng(overlay.coordinates[0].lat, overlay.coordinates[0].lng);
                  mapParams.radius = parseFloat(overlay.extraParams.radius);
                  overlayObject = new google.maps.Circle(mapParams);
                  break;

                case 'polygon':
                  mapParams.paths = overlay.coordinates;
                  overlayObject = new google.maps.Polygon(mapParams);
                  break;

                case 'polyline':
                  mapParams.path = overlay.coordinates;
                  overlayObject = new google.maps.Polyline(mapParams);
                  break;

                case 'rectangle':
                  mapParams.bounds = new google.maps.LatLngBounds(overlay.coordinates[0], overlay.coordinates[1]);
                  overlayObject = new google.maps.Rectangle(mapParams);
                  break;
              }
              if (overlay.extraParams) {
                var overlayOptions = {};
                if (!_.isUndefined(overlay.extraParams.fillColor)) {
                  overlayOptions.fillColor = overlay.extraParams.fillColor;
                }
                if (!_.isUndefined(overlay.extraParams.strokeColor)) {
                  overlayOptions.strokeColor = overlay.extraParams.strokeColor;
                }
                if (!_.isEmpty(overlayOptions)) {
                  overlayObject.setOptions(overlayOptions);
                }
              }

              if (overlayObject && (overlay.extraParams.title || overlay.extraParams.description)) {

                var infowindowMarkup = '<div>';
                if (overlay.extraParams.title) {
                  infowindowMarkup += '<h3>' + overlay.extraParams.title + '</h3>';
                }
                if (overlay.extraParams.description) {
                  infowindowMarkup += '<p>' + overlay.extraParams.description + '</p>';
                }
                infowindowMarkup += '</div>';

                overlayObject.infowindow = new google.maps.InfoWindow({
                  content: infowindowMarkup
                });

                overlayObject.addListener('click', function (event) {
                  this.infowindow.setPosition(event.latLng);
                  this.infowindow.open(map, this);
                });
              }
            }
          }
        }
        catch (e) {
          // TODO: implement.
        }
      });
    }
  };
})(jQuery, Drupal);
