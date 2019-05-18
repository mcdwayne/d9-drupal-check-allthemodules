/**
 * @file
 * Mapycz common behaviors.
 */

(function ($, window, Drupal) {
  'use strict';

  /**
   * @namespace
   */
  Drupal.mapycz = Drupal.mapycz || {};

  /**
   * Initiailizes map.
   */
  Drupal.mapycz.mapsInit = function (settings_, context) {
    // Settings.
    Drupal.mapycz.settings = {
      // Admin mode.
      admin: false,
      // Add suggestion textfield.
      suggest: false,
      // Auto compute center and zoom.
      computeCenterZoom: false,
      // Switchable layers.
      layerOptions: [],
    };
    for (var p in settings_) {
      Drupal.mapycz.settings[p] = settings_[p];
    }

    // If MapyCZ Loader is not present, lazy load it.
    if (typeof Loader === 'undefined') {
      $.getScript('https://api.mapy.cz/loader.js').done(function () {
        Drupal.mapycz._init(Drupal.mapycz.settings, context);
      });
    }
    else {
      Drupal.mapycz._init(Drupal.mapycz.settings, context);
    }
  };

  /**
   * Initialization callback from main init.
   */
  Drupal.mapycz._init = function (settings, context) {
    // Loader.
    Loader.async = true;
    Loader.load(null, { suggest: settings.suggest }, function () {

      setTimeout(function () {
        Drupal.mapycz.loadMaps(settings);
      }, 200);
    });
  };

  Drupal.mapycz.loadMaps = function (settings) {
    // Maps.
    var elementIds = [];
    var addressIds = [];
    $('.mapycz-map').each(function (idx, el) {
      elementIds.push(el.id);
    });
    $('.mapycz-address').each(function (idx, el) {
      addressIds.push(el.id);
    });
    $.each(addressIds, function (i, addressId) {
      var $wrapper = $('#' + addressId + '-wrapper');
      if ($wrapper.hasClass('processed')) {
        return true;
      }
      $wrapper.addClass('processed');
      var lng = $wrapper.find('.mapycz-location .lng').html();
      var lat = $wrapper.find('.mapycz-location .lat').html();
      var mPlace = SMap.Coords.fromWGS84(Number(lng), Number(lat));
      // Print address for field.
      new SMap.Geocoder.Reverse(mPlace, function (geocoder) {
        var result = geocoder.getResults();
        $wrapper.find('.mapycz-map-address').append('<span class="result-address">' + result.label + '</span>');
      });
    });

    $.each(elementIds, function (i, elementId) {
      // Get unprocessed wrapper.
      var $wrapper = $('#' + elementId + '-wrapper');
      if ($wrapper.hasClass('processed')) {
        return true;
      }
      $wrapper.addClass('processed');

      // Prepare map and data.
      var map = new SMap(JAK.gel(elementId), SMap.Coords.fromWGS84(0, 0), 10);
      var map_data = Drupal.mapycz.getMapData(elementId);

      // Create map default controls.
      map.addDefaultControls();

      // Disable mouse scroll zoom.
      Drupal.mapycz.disableMouseScrollZoom(map);

      // Define layers.
      var layers = {};
      layers['default'] = map.addDefaultLayer(SMap.DEF_BASE);
      layers['basic'] = map.addDefaultLayer(SMap.DEF_BASE);
      layers['satelite'] = map.addDefaultLayer(SMap.DEF_OPHOTO);
      layers['turist'] = map.addDefaultLayer(SMap.DEF_TURIST);
      layers[map_data.type].enable();

      // Prepare markers layer.
      var markerLayer = new SMap.Layer.Marker();
      map.addLayer(markerLayer);
      markerLayer.enable();

      // Array of all markers' positions.
      var clusterCoords = [];

      // Create one marker.
      var marker = new SMap.Marker(SMap.Coords.fromWGS84(0, 0), elementId + "_marker_init", {});

      // Create markers and remember their positions in cluster.
      $.each(map_data.markers, function (i, location) {
        var mPlace = SMap.Coords.fromWGS84(location.lng, location.lat);
        marker = new SMap.Marker(mPlace, elementId + "_marker_" + i, {});
        // If there is set marker title use it.
          if (location.title.length) {
          var options = {title: location.title};
          marker = new SMap.Marker(mPlace, elementId + "_marker_" + i, options);
          var card = new SMap.Card();
          card.getBody().innerHTML = location.title;
          marker.decorate(SMap.Marker.Feature.Card, card);
          if (location.print_address == true) {
            new SMap.Geocoder.Reverse(mPlace, function (geocoder) {
            var result = geocoder.getResults();
            card.getFooter().innerHTML = '<br><span class="address">' + result.label + '</span>';
            });
          }
        }
        else {
          if (location.print_address == true) {
            var card = new SMap.Card();
            var options = {title: ''};
            marker = new SMap.Marker(mPlace, elementId + "_marker_" + i, options);
            new SMap.Geocoder.Reverse(mPlace, function (geocoder) {
              var result = geocoder.getResults();
              card.getFooter().innerHTML = '<br><span class="address">' + result.label + '</span>';
            });
            marker.decorate(SMap.Marker.Feature.Card, card);
          }
        }
        markerLayer.addMarker(marker);
        clusterCoords.push(mPlace);
      });

      // Compute center and zoom automatically, or use provided values.
      if (settings.computeCenterZoom === true) {
        var clusterCenterZoom = map.computeCenterZoom(clusterCoords);
        map.setCenterZoom(clusterCenterZoom[0], clusterCenterZoom[1]);
      }
      else {
        map.setCenterZoom(SMap.Coords.fromWGS84(map_data.center.lng, map_data.center.lat), map_data.zoom);
      }

      // If admin, wire together with hidden inputs.
      if (settings.admin === true) {
        var $inputLat = $wrapper.find('.mapycz-input-location-lat');
        var $inputLng = $wrapper.find('.mapycz-input-location-lng');
        var $inputCenterLat = $wrapper.find('.mapycz-input-center-lat');
        var $inputCenterLng = $wrapper.find('.mapycz-input-center-lng');
        var $inputZoom = $wrapper.find('.mapycz-input-zoom');
        var $inputType = $wrapper.find('.mapycz-input-type');

        // Change location on click.
        map.getSignals().addListener(window, "map-click", function (e, elm) {
          var coords = SMap.Coords.fromEvent(e.data.event, map);
          marker.setCoords(coords);
          $inputLat.val(coords.y);
          $inputLng.val(coords.x);
          $inputLat.trigger('change');
          $inputLng.trigger('change');
          markerLayer.addMarker(marker);
        });

        // Change center on pan.
        map.getSignals().addListener(window, "control-mouse-move", function (e, elm) {
          var coords = map.getCenter();
          $inputCenterLat.val(coords.y);
          $inputCenterLng.val(coords.x);
        });

        // Change zoom on wheel.
        map.getSignals().addListener(window, "zoom-stop", function (e, elm) {
          $inputZoom.val(map.getZoom());
        });
      }

      var $controls = $wrapper.find('.mapycz-admin-controls');

      // Add admin controls - suggest.
      if (settings.suggest === true) {
        var $input = $('<input>', {
          id: elementId + '-suggest',
          type: 'text',
          placeholder: 'Napište lokaci',
          class: 'form-text',
        });

        $controls.append($input);
        var suggestInput = new SMap.Suggest($input[0]);
        suggestInput.addListener('suggest', function (suggestData) {
          var suggestCenter = SMap.Coords.fromWGS84(suggestData.data.longitude, suggestData.data.latitude);
          // Place one marker (or add one if none) to suggested area.
          if (settings.admin === true) {
            if (clusterCoords.length == 0) {
              markerLayer.addMarker(marker);
            }
            marker.setCoords(suggestCenter);
            // Also change input values.
            $inputLat.val(suggestCenter.y);
            $inputLng.val(suggestCenter.x);
            $inputLat.trigger('change');
            $inputLng.trigger('change');
            $inputCenterLat.val(suggestCenter.y);
            $inputCenterLng.val(suggestCenter.x);
            $inputZoom.val(12);
          }
          // Set map to new center with zoom on city level (=12).
          map.setCenterZoom(suggestCenter, 12);
        });
      }

      // Add admin controls - clear button.
      if (settings.suggest === true) {
        var $clear = $('<button>', {
          id: elementId + '-clear',
          text: 'Zrušit',
          class: 'button',
          disabled: true,
          click: function () {
            $inputLat.val('');
            $inputLng.val('');
            $inputLat.trigger('change');
            $inputLng.trigger('change');
            markerLayer.removeAll();
            clusterCoords = [];
            return false;
          },
        });
        $controls.append($clear);
      }

      // Add layer switcher if any.
      if (settings.layerOptions.length !== 0) {
        var $select = $('<select>', {
          id: elementId + '-switch-layer',
          class: 'select',
          change: function () {
            $.each(layers, function (i, v) {
              layers[i].disable();
            });
            layers[$(this).val()].enable();
            if (settings.admin === true) {
              $inputType.val($(this).val());
            }
          },
        });
        $select.append($('<option>', {value: 'default', text: 'Výchozí'}));
        $.each(settings.layerOptions, function (k, v) {
          $select.append($('<option>', {value: k, text: v}));
        });
        $select.val($inputType.val());
        $controls.append($select);
      }

      // Clear button disable and enable.
      if (settings.admin === true) {
        $inputLat.change(function () {
          if ($(this).val() == '') {
            $clear.prop('disabled', true);
          }
          else {
            $clear.prop('disabled', false);
          }
          var center = map.getCenter();
          $inputCenterLat.val(center.y);
          $inputCenterLng.val(center.x);
          $inputZoom.val(map.getZoom());
          $inputType.val($('#' + elementId + '-switch-layer').val());
        });
        $inputLng.change(function () {
          if ($(this).val() == '') {
            $clear.prop('disabled', true);
          }
          else {
            $clear.prop('disabled', false);
          }
          var center = map.getCenter();
          $inputCenterLat.val(center.y);
          $inputCenterLng.val(center.x);
          $inputZoom.val(map.getZoom());
          $inputType.val($('#' + elementId + '-switch-layer').val());
        });
        $inputLat.trigger('change');
        $inputLng.trigger('change');
      }
    });
  };

  /**
   * Parses map's data from HTML structure.
   *
   * @param mapId
   *   Id of map - div with mapycz-map class.
   *
   * @returns
   *   Data object.
   */
  Drupal.mapycz.getMapData = function (mapId) {
    var $wrapper = $('#' + mapId + '-wrapper');
    var data = {};
    data.type = $wrapper.find('.mapycz-settings .mapycz-type').html();
    data.zoom = parseInt($wrapper.find('.mapycz-settings .mapycz-zoom').html());
    data.center = {};
    data.center.lng = $wrapper.find('.mapycz-settings .mapycz-center .lng').html();
    data.center.lat = $wrapper.find('.mapycz-settings .mapycz-center .lat').html();
    data.markers = [];
    $wrapper.find('.mapycz-location').each(function (i, location) {
      var marker = {};
      marker.lat = $(location).find('.lat').html();
      marker.lng = $(location).find('.lng').html();
      marker.title = $(location).find('.marker-title').html();
      marker.print_address = $wrapper.find('.marker-address').html();
      data.markers.push(marker);
    });
    return data;
  };

  Drupal.mapycz.disableMouseScrollZoom = function (map) {
    var controls = map.getControls();
    for (var i = 0; i < controls.length; i++) {
      if (controls[i] instanceof SMap.Control.Mouse) {
        controls[i].configure(SMap.MOUSE_PAN | SMap.MOUSE_ZOOM);
      }
    }
  }

})(jQuery, window, Drupal);
