/**
 * @file
 * Initialize object extended to jQuery for global operations.
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.ymaps_geolocation_formatter = {
    attach: function (context) {

      if (drupalSettings.ymaps) {
        $('div.ymaps-geolocation-map', context).once('ymaps').each(function () {
          var mapId = this.id;

          ymaps.ready(function () {
            Drupal.geolocationYmap.mapInit(mapId);
          });
        });
      }

      Drupal.geolocationYmap = Drupal.geolocationYmap || {

        // Yandex map initialization.
        mapInit: function (mapId) {

          var options = drupalSettings.ymaps[mapId];
          var map;


          if (options.edit) {

            // Field geolocation Yandex map edit widget.
            if (options.placemark.coordinates[0] && options.placemark.coordinates[1]) {
              map = new ymaps.Map(mapId, options.init);
              Drupal.geolocationYmap.autoCentering(map);
              Drupal.geolocationYmap.autoZooming(map);
            }
            else {
              options.init.center = [55.75370903771494, 37.61981338262558];
              map = new ymaps.Map(mapId, options.init);
            }

            map.events.add('boundschange', function (event) {
              var newCenter = event.get('newCenter');
              $('.field-ymaps-lat-' + mapId).val(newCenter[0]);
              $('.field-ymaps-lng-' + mapId).val(newCenter[1]);
            });

            $('.field-group-tabs-wrapper ul li').click(function () {
              map.container.fitToViewport();
            });
          }
          else {

            map = new ymaps.Map(mapId, options.init);

            var placemark = new ymaps.Placemark(
              {
                type: 'Point',
                coordinates: options.placemark.coordinates
              },
              {
                balloonContent: options.placemark.balloonContent
              },
              {
                preset: options.placemark.preset
              });

            map.geoObjects.add(placemark);

            // Auto centering
            if (options.display.auto_centering || !options.init.center || (options.init.center[0] === 0 && options.init.center[1] === 0)) {
              Drupal.geolocationYmap.autoCentering(map);
            }
            // Auto zooming
            if (options.display.auto_zooming || !options.init.zoom) {
              Drupal.geolocationYmap.autoZooming(map);
            }

          }

          map.controls.get('zoomControl').options.set({size: 'small'});


          return map;
        },

        // Auto centering map.
        autoCentering: function (map) {
          if (map.geoObjects.getLength() === 0) {
            return;
          }
          var centerAndZoom = ymaps.util.bounds.getCenterAndZoom(map.geoObjects.getBounds(), map.container.getSize());
          map.setCenter(centerAndZoom.center);
        },

        // Auto zooming map.
        autoZooming: function (map) {
          if (map.geoObjects.getLength() === 0) {
            return;
          }
          var mapSize = map.container.getSize();
          var centerAndZoom = ymaps.util.bounds.getCenterAndZoom(
            map.geoObjects.getBounds(),
            mapSize,
            ymaps.projection.wgs84Mercator,
            {margin: 30}
          );
          map.setZoom(centerAndZoom.zoom <= 16 ? centerAndZoom.zoom : 16);
        }
      };
    }
  };

})(jQuery, Drupal);
