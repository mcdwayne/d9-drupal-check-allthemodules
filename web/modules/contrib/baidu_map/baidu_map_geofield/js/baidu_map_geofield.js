/**
 * @file
 * Behaviors for the Baidu Map Geofield module based on Baidu Map JS API V2.
 *
 * @link http://developer.baidu.com/map/reference/
 */

(function ($, Drupal, drupalSettings) {
    /**
     * Instantiate all Baidu Maps with configured display settings.
     */
    Drupal.behaviors.geofieldBaiduMap = {
        attach: function (context, drupalSettings) {
            Drupal.geoField = Drupal.geoField || {};
            Drupal.geoField.maps = Drupal.geoField.maps || {};

            if (drupalSettings['baidu_map_geofield']) {
                $(context).find('.geofield_baidu_map').once('geofield-processed').each(function (index, element) {
                    var mapid = $(element).attr('id');

                    // Check if the Map container really exists and hasn't been yet initialized.
                    if (drupalSettings['baidu_map_geofield'][mapid] && !Drupal.geoFieldBaiduMap.map_data[mapid]) {

                        var map_settings = drupalSettings['baidu_map_geofield'][mapid]['map_settings'];
                        var data = drupalSettings['baidu_map_geofield'][mapid]['data'];

                        // Set the map_data[mapid] settings.
                        Drupal.geoFieldBaiduMap.map_data[mapid] = map_settings;

                        //// Load before the Gmap Library, if needed.
                        Drupal.geoFieldBaiduMap.map_initialize(mapid, map_settings, data);
                    }
                })
            }
        }
    };

    Drupal.geoFieldBaiduMap = {

        map_data: {},
        geocoder: {},
        all_points: [],
        // Init Geofield Google Map and its functions.
        map_initialize: function (mapid, map_settings, data) {
            var self = this;
            $.noConflict();

            //添加地图类型控件
            // Map type defaults to "NORMAL".
            var maptype = {
                'mapType': BMAP_NORMAL_MAP
            };

            switch (map_settings.maptype) {
                case 'perspective':
                    maptype.mapType = BMAP_PERSPECTIVE_MAP;
                    break;

                case 'satellite':
                    maptype.mapType = BMAP_SATELLITE_MAP;
                    break;

                case 'hybrid':
                    // Currently, only supported for Beijing, Shanghai and Guangzhou.
                    maptype.mapType = BMAP_HYBRID_MAP;
                    break;
            }

            self.geocoder = new BMap.Geocoder();

            // Instantiate Baidu Map.
            var map = new BMap.Map(mapid, maptype);

            self.map_data[mapid].map = map;

            var map_style_settings = map_settings.map_style;
            // Set the map style.
            var mapStyle = {
                features: ["road", "building", "water", "land", "point"],
                style: map_style_settings['baidu_map_geofield_style']
            };
            map.setMapStyle(mapStyle);

            // Enable Zoom in or out with mouse wheel, disabled by default.
            if (map_style_settings['baidu_map_geofield_scrollwheel']) {
                map.enableScrollWheelZoom();
            }

            // Disable Dragging behavior for the map, enabled by default.
            if (!map_style_settings['baidu_map_geofield_draggable']) {
                map.disableDragging();
            }

            // Show traffic, disabled by default.
            if (map_style_settings['baidu_map_geofield_showtraffic']) {
                var traffic = new BMap.TrafficLayer();
                map.addTileLayer(traffic);
            }

            // Map scale hidden by default.
            if (map_style_settings['baidu_map_geofield_scalecontrol']) {
                map.addControl(new BMap.ScaleControl());
            }

            // Navigation controls hidden by default.
            if (map_style_settings['baidu_map_geofield_navigationcontrol']) {
                // Navigation controls defaults to "BMAP_NAVIGATION_CONTROL_LARGE".
                var opts = {}
                switch (map_style_settings['baidu_map_geofield_navigationcontrol']) {
                    case 'large':
                        opts.type = BMAP_NAVIGATION_CONTROL_LARGE;
                        break;

                    case 'pan':
                        opts.type = BMAP_NAVIGATION_CONTROL_PAN;
                        break;

                    case 'small':
                        opts.type = BMAP_NAVIGATION_CONTROL_SMALL;
                        break;

                    case 'zoom':
                        opts.type = BMAP_NAVIGATION_CONTROL_ZOOM;
                        break;
                }
                // Add Navigation Controls to the map.
                map.addControl(new BMap.NavigationControl(opts));
            }

            // Map type control hidden by default.
            if (map_style_settings['baidu_map_geofield_maptypecontrol']) {
                map.addControl(new BMap.MapTypeControl({mapTypes: [BMAP_NORMAL_MAP, BMAP_HYBRID_MAP, BMAP_PERSPECTIVE_MAP]}));
            }

            if (data != undefined) {
                var features = BaiduMapGeoJSON(data);
                // Store all points to be displayed with automatic zoom and center.
                var range = new BMap.Bounds();
                // Attach all geometries to the Baidu Map instance.
                if (features.getMap) {
                    // Currently, there is no support for better handling of the zoom.
                    this.placeFeature(features, map, range);
                } else {
                    for (var i in features) {
                        if (features[i].getMap) {
                            this.placeFeature(features[i], map, range);
                        } else {
                            for (var j in features[i]) {
                                // Baidu Map handles each path as a separate Overlay.
                                if (features[i][j].getMap) {
                                    this.placeFeature(features[i][j], map, range);
                                }
                                else {
                                    for (var k in features[i][j]) {
                                        if (features[i][j][k].getMap) {
                                            this.placeFeature(features[i][j][k], map, range);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if (map_style_settings.baidu_map_geofield_zoom == 'auto') {
                    // Automatically zoom and center on all the points.
                    map.setViewport(this.all_points);
                }
                else {
                    // Set the default center and zoom value.
                    map.centerAndZoom(range.getCenter(), new Number(map_style_settings.baidu_map_geofield_zoom));
                }
            }
        },

        placeFeature: function (feature, map, range) {
            var self = this;
            /**
             * Helper function to add a Point or a path to a Baidu Map Overlay.
             */
            var properties = feature.geojsonProperties;
            // Only supported by Markers: set the title property.
            if (feature.setTitle && properties && properties.title) {
                feature.setTitle(properties.title);
            }
            // Add the feature to the map in an Overlay.
            map.addOverlay(feature);
            if (feature.getPosition) {
                // Extend bounds/range for each Point.
                range.extend(feature.getPosition());
                self.all_points.push(feature.getPosition());
            } else {
                // Extend bounds/range for each path.
                var path = feature.getPath();
                path.forEach(function (element) {
                    range.extend(element);
                    self.all_points.push(element);
                });
            }
            // Attach InfoWindow to Markers if there is any content to display.
            if (properties && properties.description) {
                var bounds = feature.bounds;
                // Only supported by Markers: attach InfoWindow on click event.
                if (feature.openInfoWindow) {
                    feature.addEventListener('click', function () {
                        // Centering is automatic for InfoWindow.
                        infowindow.setContent(properties.description);
                        this.openInfoWindow(infowindow, map.getCenter());
                    });
                }
            }
            //self.infos.push(properties.description);
            //self.markers.push(feature);
        }
    };
})(jQuery, Drupal, drupalSettings);
