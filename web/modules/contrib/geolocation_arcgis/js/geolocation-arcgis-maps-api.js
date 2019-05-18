/**
 * @file
 *   Javascript for the ArcGIS Geocoding API geocoder.
 */

(function ($, Drupal) {
    'use strict';

    Drupal.geolocation.geolocation_arcgis = Drupal.geolocation.geolocation_arcgis || {};
    Drupal.geolocation.geolocation_arcgis.addLoadedCallback = function(cb) {
        if (Drupal.geolocation.geolocation_arcgis._isLoaded) {
            cb();
            return;
        }
        Drupal.geolocation.geolocation_arcgis.onLoadedCallbacks = Drupal.geolocation.geolocation_arcgis.onLoadedCallbacks || [];
        Drupal.geolocation.geolocation_arcgis.onLoadedCallbacks.push(cb);
    };
    Drupal.geolocation.geolocation_arcgis.load = function() {
        if (Drupal.geolocation.geolocation_arcgis._isLoaded) {
            $.each(Drupal.geolocation.geolocation_arcgis.onLoadedCallbacks, function(idx, cb) {
                cb();
            });
            Drupal.geolocation.geolocation_arcgis.onLoadedCallbacks = [];
            return;
        }
        if (typeof require !== 'function') {
            setTimeout(Drupal.geolocation.geolocation_arcgis.load, 500);
            return;
        }
        require([
            "esri/Map",
            "esri/views/SceneView",
            "esri/widgets/Search",
            "esri/tasks/Locator",
            "esri/layers/GraphicsLayer",
            "esri/Graphic"
        ], function (_m, _sv, _s, _l, _gl, _g) {
            Drupal.geolocation.geolocation_arcgis.Map = _m;
            Drupal.geolocation.geolocation_arcgis.SceneView = _sv;
            Drupal.geolocation.geolocation_arcgis.Search = _s;
            Drupal.geolocation.geolocation_arcgis.Locator = _l;
            Drupal.geolocation.geolocation_arcgis.GraphicsLayer = _gl;
            Drupal.geolocation.geolocation_arcgis.Graphic = _g;
            $.each(Drupal.geolocation.geolocation_arcgis.onLoadedCallbacks, function(idx, cb) {
                cb();
            });
            Drupal.geolocation.geolocation_arcgis.onLoadedCallbacks = [];
            Drupal.geolocation.geolocation_arcgis._isLoaded = true;
        });
    };

    function GeolocationArcGISAPI(mapSettings) {
        this.type = 'arcgis_maps';
        let that = this;
        Drupal.geolocation.GeolocationMapBase.call(this, mapSettings);
        this.addReadyCallback(function(map) {
            map.arcgis = map.arcgis || {};
            map.arcgis.map = new Drupal.geolocation.geolocation_arcgis.Map({
                basemap: map.settings.map_settings.map_type || "streets-vector"
            });
            let home = null;
            if (mapSettings.centreBehavior == 'preset') {
                home = [mapSettings.lng, mapSettings.lat];
            }
            let sceneView = {
                container: map.wrapper.find('.geolocation-map-container')[0],
                map: map.arcgis.map,
                zoom: 12,
                center: home || map.settings.map_settings.home_location
            };
            map.arcgis.view = new Drupal.geolocation.geolocation_arcgis.SceneView(sceneView);
            let graphicsLayer = new Drupal.geolocation.geolocation_arcgis.GraphicsLayer();
            map.arcgis.graphics = graphicsLayer;
            map.arcgis.map.add(graphicsLayer);

            if (map.settings.map_settings.home_location && map.settings.map_settings.home_location_marker) {
                let home = map.settings.map_settings.home_location;
                let settings = {
                    geometry: {
                        type: 'point',
                        latitude: home[1],
                        longitude: home[0]
                    }
                };
                map.setMapMarker(settings);
            }
    
            if (map.settings.map_settings.showGeocoder) {
                let searchOptions = {
                    view: map.arcgis.view,
                    locationEnabled: true
                };
                if (map.settings.map_settings.geocodeProxyUrl) {
                    let source = {
                        locator: new Drupal.geolocation.geolocation_arcgis.Locator({
                            url: map.settings.map_settings.geocodeProxyUrl
                        }),
                        name: 'Default Geocoder',
                        outFields: ["Addr_type"],
                        singleLineFieldName: "SingleLine",
                        localSearchOptions: {
                            minScale: 300000,
                            distance: 50000
                        },
                        placeholder: map.settings.map_settings.placeholder
                    };
                    searchOptions.sources = [source];
                    searchOptions.includeDefaultSources = false;
                }
                map.arcgis.searchWidget = new Drupal.geolocation.geolocation_arcgis.Search(searchOptions);
                // Add the search widget to the top right corner of the view
                map.arcgis.view.ui.add(map.arcgis.searchWidget, {
                    position: map.settings.map_settings.geocoderLocation || "top-right"
                });
                map.arcgis.view.when(function(v) {
                    v.on('click', function(event) {
                        v.hitTest(event).then(function(testResults) {
                            // User didn't click on a marker.
                            if (testResults.results[0].graphic == null) {
                                that.clickCallback(event);
                            }
                        })
                    });
                });
                map.arcgis.searchWidget.on('select-result', function(event) {
                    that.searchCallback(event);
                });
            }
            that.loadedCallback();
        });

        this.wrapperReady = function() {
            if (!mapSettings.wrapper.is(':visible')) {
                setTimeout(that.wrapperReady, 500);
            }
            else {
                if (this.ready) {
                    this.readyCallback();
                }
                else {
                    Drupal.geolocation.geolocation_arcgis.addLoadedCallback(function() {
                        that.readyCallback();
                    });
                }
            }
        }

        setTimeout(this.wrapperReady, 1000);
        if (!Drupal.geolocation.geolocation_arcgis._isLoaded) {
            Drupal.geolocation.geolocation_arcgis.load();
        }
    }
    GeolocationArcGISAPI.prototype = Object.create(Drupal.geolocation.GeolocationMapBase.prototype);
    GeolocationArcGISAPI.prototype.constructor = GeolocationArcGISAPI;
    GeolocationArcGISAPI.prototype.addControl = function(ctrl) { };
    GeolocationArcGISAPI.prototype.setMapMarker = function(markerSettings) {
        if (markerSettings.attributes != null && markerSettings.attributes.delta != null) {
            let marker = this.getMarkerByDelta(markerSettings.attributes.delta);
            if (marker !== null) {
                this.removeMarker(marker);
            }
        }
        markerSettings.geometry = markerSettings.geometry || {
            type: 'point',
            latitude: markerSettings.position.lat,
            longitude: markerSettings.position.lng
        };
        if (markerSettings.geometry.type == null) {
            markerSettings.geometry.type = 'point';
        }
        markerSettings.symbol = markerSettings.symbol || {
            type: "simple-marker",
            color: [0,0,0],
            style: 'circle',
            size: 9,
            outline: {
                color: [255,255,255],
                width: 2
            }
        };
        markerSettings.attributes = $.extend(markerSettings.attributes, {
            LAT: markerSettings.geometry.latitude,
            LNG: markerSettings.geometry.longitude
        });
        markerSettings.popupTemplate = markerSettings.popupTemplate || {
            content: "<strong>Latitude:</strong>&nbsp;{LAT}<br /><strong>Longitude:</strong>&nbsp;{LNG}"
        };
        let pointGraphic = new Drupal.geolocation.geolocation_arcgis.Graphic(markerSettings);
        this.arcgis.graphics.add(pointGraphic);
        if (markerSettings.goTo) {
            this.arcgis.view.goTo(pointGraphic);
        }
        this.markerAddedCallback(pointGraphic);
        return pointGraphic;
    };
    GeolocationArcGISAPI.prototype.removeMarker = function(marker) {
        this.arcgis.graphics.remove(marker);
        this.markerRemoveCallback(marker);
    };
    GeolocationArcGISAPI.prototype.getMarkerByDelta = function(delta) {
        let marker = null;
        $.each(this.arcgis.map.layers.items, function(idx, layer) {
            $.each(layer.graphics.items, function(gidx, graphic) {
                if (graphic.attributes && graphic.attributes.delta == delta) {
                    marker = graphic;
                }
            })
        });
        return marker;
    };
    GeolocationArcGISAPI.prototype.fitMapToMarkers = function() {
        let p = 0;
        let minLat = 90;
        let minLng = 180;
        let maxLat = -90;
        let maxLng = -180;
        $.each(this.arcgis.map.layers.items, function(idx, layer) {
            $.each(layer.graphics.items, function(gidx, graphic) {
                if (graphic.geometry.latitude < minLat) {
                    minLat = graphic.geometry.latitude;
                }
                if (graphic.geometry.latitude > maxLat) {
                    maxLat = graphic.geometry.latitude;
                }
                if (graphic.geometry.longitude < minLng) {
                    minLng = graphic.geometry.longitude;
                }
                if (graphic.geometry.longitude > maxLng) {
                    maxLng = graphic.geometry.longitude;
                }
                p++;
            })
        });
        if (p == 0) {
            return;
        }
        let center = [
            (maxLng + minLng) / 2,
            (maxLat + minLat) / 2
        ];
        this.arcgis.view.when(function(view) {
            view.goTo(center);
        });
    };
    GeolocationArcGISAPI.prototype.fitBoundaries = function() {
    };
    GeolocationArcGISAPI.prototype.addSearchCallback = function(callback) {
        this.searchCallbacks = this.searchCallbacks || [];
        this.searchCallbacks.push(callback);
    };
    GeolocationArcGISAPI.prototype.searchCallback = function(event) {
        this.searchCallbacks = this.searchCallbacks || [];
        $.each(this.searchCallbacks, function (index, callback) {
          callback(event);
        });
    };
    Drupal.geolocation.GeolocationArcGISAPI = GeolocationArcGISAPI;
    Drupal.geolocation.addMapProvider("arcgis_maps", 'GeolocationArcGISAPI');

})(jQuery, Drupal);
