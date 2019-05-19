/**
 * @file
 * Initiates map(s) for the Styled Google Map module.
 *
 * A single or multiple Styled Google Maps will be initiated.
 * Drupal behaviors are used to make sure ajax called map(s) are correctly loaded.
 */

(function ($) {
  Drupal.behaviors.styled_google_maps = {
    attach: function (context, settings) {
      var maps = settings.styled_google_map;
      var markers = [];
      for (var i in maps) {
        var current_map = settings.maps['id' + maps[i]];
        var map_id = current_map.id;
        if ($('#' + map_id).length) {
          var map_locations = current_map.locations;
          var map_settings = current_map.settings;
          var bounds = new google.maps.LatLngBounds();
          var map_types = {
            'ROADMAP': google.maps.MapTypeId.ROADMAP,
            'SATELLITE': google.maps.MapTypeId.SATELLITE,
            'HYBRID': google.maps.MapTypeId.HYBRID,
            'TERRAIN': google.maps.MapTypeId.TERRAIN
          };
          $('#' + map_id).css({'width':current_map.settings.width, 'height' : current_map.settings.height});
          var map_style = (map_settings.style.style != '' ? map_settings.style.style : '[]');

          map_settings.draggable = $(window).width() > 480 ? map_settings.draggable : map_settings.mobile_draggable;

          var init_map = {
            gestureHandling: map_settings.gestureHandling,
            zoom: parseInt(map_settings.zoom.default),
            mapTypeId: map_types[map_settings.style.maptype],
            disableDefaultUI: !map_settings.ui,
            maxZoom: parseInt(map_settings.zoom.max),
            minZoom: parseInt(map_settings.zoom.min),
            styles: JSON.parse(map_style),
            mapTypeControl: map_settings.maptypecontrol,
            scaleControl: map_settings.scalecontrol,
            rotateControl: map_settings.rotatecontrol,
            streetViewControl: map_settings.streetviewcontrol,
            zoomControl: map_settings.zoomcontrol,
            draggable: map_settings.draggable
          };
          var map = new google.maps.Map(document.getElementById(map_id), init_map);
          settings.initialized_styles_google_maps = settings.initialized_styles_google_maps || [];
          settings.initialized_styles_google_maps.push(map);
          var infoBubble = new InfoBubble({
            shadowStyle: parseInt(map_settings.popup.shadow_style),
            padding: parseInt(map_settings.popup.padding),
            borderRadius: parseInt(map_settings.popup.border_radius),
            borderWidth: parseInt(map_settings.popup.border_width),
            borderColor: map_settings.popup.border_color,
            backgroundColor: map_settings.popup.background_color,
            minWidth: map_settings.popup.min_width,
            maxWidth: map_settings.popup.max_width,
            maxHeight: map_settings.popup.min_height,
            minHeight: map_settings.popup.max_height,
            arrowStyle: parseInt(map_settings.popup.arrow_style),
            arrowSize: parseInt(map_settings.popup.arrow_size),
            arrowPosition: parseInt(map_settings.popup.arrow_position),
            disableAutoPan: parseInt(map_settings.popup.disable_autopan),
            disableAnimation: parseInt(map_settings.popup.disable_animation),
            hideCloseButton: parseInt(map_settings.popup.hide_close_button),
            backgroundClassName: map_settings.popup.classes.background
          });
          // Set extra custom classes for easy styling.
          if (typeof map_settings.popup.close_button_source != 'undefined' && map_settings.popup.close_button_source) {
            infoBubble.close_.src = map_settings.popup.close_button_source;
          }
          infoBubble.contentContainer_.className = map_settings.popup.classes.container;
          infoBubble.arrow_.className = map_settings.popup.classes.arrow;
          infoBubble.arrowOuter_.className = map_settings.popup.classes.arrow_outer;
          infoBubble.arrowInner_.className = map_settings.popup.classes.arrow_inner;
          if (typeof map_settings.cluster != 'undefined' &&  map_settings.cluster.cluster_enabled) {
            var clusterStyles = [
              {
                textColor: map_settings.cluster.text_color,
                url: map_settings.cluster.pin_image,
                height: parseInt(map_settings.cluster.height),
                width: parseInt(map_settings.cluster.width),
                textSize: parseInt(map_settings.cluster.text_size)
              }
            ];
            var mcOptions = {
              gridSize: 60,
              zoomOnClick: map_settings.cluster.zoomOnClick,
              maxZoom: map_settings.zoom.max - 1,
              styles: clusterStyles,
              minimumClusterSize: parseInt(map_settings.cluster.min_size),
            };
          }
          if (typeof map_settings.spider != 'undefined' &&  map_settings.spider.spider_enabled) {
            var spiderConfig = {
              markersWontMove: map_settings.spider.markers_wont_move,
              markersWontHide: map_settings.spider.markers_wont_hide,
              basicFormatEvents: map_settings.spider.basic_format_events,
              keepSpiderfied: map_settings.spider.keep_spiderfied,
              nearbyDistance: map_settings.spider.nearby_distance,
              circleSpiralSwitchover: map_settings.spider.circle_spiral_switchover,
              legWeight: map_settings.spider.leg_weight
            };
            // Init OverlappingMarkerSpiderfier with map.
            var markerSpiderfier = new OverlappingMarkerSpiderfier(map, spiderConfig);
          }
          if (typeof map_settings.heat_map != 'undefined' && map_settings.heat_map.heatmap_enabled) {
            var heatmap_data = [];
            for (var k in map_settings.heat_map.data) {
              var heatmap_item = new google.maps.LatLng(map_settings.heat_map.data[k].lat , map_settings.heat_map.data[k].lon);
              heatmap_data.push(heatmap_item);
            }
            var heatmap = new google.maps.visualization.HeatmapLayer({
              data: heatmap_data,
              map: map,
              gradient: map_settings.heat_map.gradient,
              opacity: map_settings.heat_map.opacity,
              maxIntensity: map_settings.heat_map.maxIntensity,
              dissipating: map_settings.heat_map.dissipating,
              radius: map_settings.heat_map.radius
            });
          }
          for (var j in map_locations) {
            var marker = new google.maps.Marker({
              position: new google.maps.LatLng(map_locations[j].lat , map_locations[j].lon),
              map: map,
              html: map_locations[j].popup,
              label: map_locations[j].marker_label,
              icon: map_locations[j].pin,
              original_icon: map_locations[j].pin,
              active_icon: map_locations[j].pin,
              category: map_locations[j].category
            });
            markers.push(marker);
            if (typeof map_settings.spider != 'undefined' &&  map_settings.spider.spider_enabled) {
              // Add the Marker to OverlappingMarkerSpiderfier.
              markerSpiderfier.addMarker(marker);
            }
            if (map_locations[j].popup) {
              if (typeof map_settings.popup.default_state != 'undefined' && map_settings.popup.default_state == 1 && j == 0) {
                infoBubble.setContent(marker.html);
                infoBubble.bubble_.className = 'sgmpopup sgmpopup-' + marker.category;
                infoBubble.open(map, marker);
              }
              var open_event = map_settings.popup.open_event;
              google.maps.event.addListener(marker, open_event, (function (map) {
                  return function () {
                      infoBubble.setContent(this.html);
                      for (var i = 0; i < markers.length; i++) {
                         markers[i].setIcon(markers[i].original_icon);
                         infoBubble.bubble_.className = 'sgmpopup sgmpopup-' + markers[i].category;
                      }
                      this.setIcon(this.active_icon);
                      infoBubble.open(map, this);
                  };
              }(map)));
            }
            // Add spider event listeners.
            if (typeof map_settings.spider != 'undefined' &&  map_settings.spider.spider_enabled) {
              //Set original pins when siderfy
              markerSpiderfier.addListener('spiderfy', function (markers) {
                for (var i = 0; i < markers.length; i++) {
                  markers[i].setIcon(markers[i].original_icon);
                  markers[i].setZIndex(1);
                }
              });
              // Set pin with + when unspiderfy.
              markerSpiderfier.addListener('unspiderfy', function (markers) {
                for (var i = 0; i < markers.length; i++) {
                  markers[i].setIcon(map_settings.spider.pin_image);
                  infoBubble.close();
                }
              });
            }
            bounds.extend(marker.getPosition());
          }
          // Centering map.
          if (typeof map_settings.cluster != 'undefined' &&  map_settings.cluster.cluster_enabled) {
            var markerCluster = new MarkerClusterer(map, markers, mcOptions);
            markerCluster.setMaxZoom(mcOptions.maxZoom);
          }
        }
        if (map_settings.map_center && map_settings.map_center.center_coordinates) {
          if (!isNaN(parseInt(map_settings.map_center.center_coordinates.lat)) && !isNaN(parseInt(map_settings.map_center.center_coordinates.lon))) {
            var map_center = new google.maps.LatLng(map_settings.map_center.center_coordinates.lat, map_settings.map_center.center_coordinates.lon);
            bounds.extend(map_center);
            map.setCenter(map_center);
          }
        }
        else {
          map.setCenter(bounds.getCenter());
        }
        // This is needed to set the zoom after fitbounds.
        google.maps.event.addListener(map, 'zoom_changed', function () {
          var zoomChangeBoundsListener =
          google.maps.event.addListener(map, 'bounds_changed', function (event) {
            var current_zoom = this.getZoom();
            if (current_zoom > parseInt(map_settings.zoom.default) && map.initialZoom == true) {
              // Change max/min zoom here.
              this.setZoom(parseInt(map_settings.zoom.default) - 1);
            }
            map.initialZoom = false;
            google.maps.event.removeListener(zoomChangeBoundsListener);
          });
        });
        if (typeof map_settings.spider != 'undefined' &&  map_settings.spider.spider_enabled) {
          google.maps.event.addListener(map, 'idle', function (marker) {
            // Change spiderable markers to plus sign markers
            // and subsequently any other zoom/idle.
            var spidered = markerSpiderfier.markersNearAnyOtherMarker();
            for (var i = 0; i < spidered.length; i++) {
              // Set spidered icon when inside cluster.
              spidered[i].setIcon(map_settings.spider.pin_image);
              spidered[i].setZIndex(0);
            }
          });
        }
        map.initialZoom = true;
        map.fitBounds(bounds);
      }
      // Prevents piling up generated map ids.
      settings.styled_google_map = [];
    }
  };

})(jQuery);
