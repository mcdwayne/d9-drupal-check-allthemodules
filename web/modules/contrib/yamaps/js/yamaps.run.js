/**
 * @file
 * Runs map.
 */

(function($, Drupal, drupalSettings) {
  Drupal.behaviors.yamapsRun = {
    attach: function (context, settings) {
      ymaps.ready(function () {
        function creating_map(mapId, options) {
          $('#' + mapId).once('yamaps').each(function (index, item) {
            var initPromise = new Promise(function(resolve, reject){
              if (options.init.center) {
                resolve(options.init.center);
              }
              else {
                // Get map center by geolocation ip.
                ymaps.geolocation.get({
                  provider: 'yandex',
                }).then(function(result) {
                  resolve(result.geoObjects.position);
                })
              }
            });
            initPromise.then(function(center) {
              options.init.center = center;

              //Remove default controls.
              options.init.controls = [];
              if (!options.init.zoom) {
                options.init.zoom = location.zoom ? location.zoom : 10;
              }
              // Create new map.
              var map = new $.yaMaps.YamapsMap(mapId, options);
              if (options.controls) {
                // Enable controls.
                map.enableControls();
              }

              // Enable plugins.
              map.enableTools();
            });
          });
        }

        function processMaps() {
          if (drupalSettings.yamaps) {
            for (var mapId in drupalSettings.yamaps) {
              var options = drupalSettings.yamaps[mapId];
              creating_map(mapId, options);
            }
          }
        }

        function openMap(selectorOpen, selectorClose) {
          $('div' + selectorOpen).click(function() {
            var mapId = $(this).attr('mapid');
            $('#' + mapId).removeClass('element-invisible');
            $(this).addClass('element-invisible');
            $('div[mapid="' + mapId + '"]' + selectorClose).removeClass('element-invisible');
          });

          $('div' + selectorClose).click(function() {
            var mapId = $(this).attr('mapid');
            $('#' + mapId).addClass('element-invisible');
            $(this).addClass('element-invisible');
            $('div[mapid="' + mapId + '"]' + selectorOpen).removeClass('element-invisible');
          })
        }

        // Initialize layouts.
        $.yaMaps.initLayouts();
        processMaps();
        openMap('.open_yamap_button', '.close_yamap_button');
      })
    }
  }
})(jQuery, Drupal, drupalSettings);
