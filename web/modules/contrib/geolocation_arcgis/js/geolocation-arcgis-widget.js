(function ($, Drupal) {
    'use strict';

    function GeolocationArcGISMapWidget(widgetSettings) {
        Drupal.geolocation.widget.GeolocationMapWidgetBase.call(this, widgetSettings);
        this.currentDelta = 0;
        let that = this;
        if (widgetSettings.autoClientLocationMarker && "geolocation" in navigator) {
            this.map.addReadyCallback(function() {
                let input = that.getAllInputs().eq(0);
                let lng = input.find('input.geolocation-map-input-longitude').val();
                let lat = input.find('input.geolocation-map-input-latitude').val();
                if (!lng || !lat) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        that.map.clickCallback({
                            mapPoint: {
                                latitude: position.coords.latitude,
                                longitude: position.coords.longitude,
                            }
                        });
                    });
                }
            });
        }
        this.map.addReadyCallback(function() {
            that.map.addSearchCallback(function(event) {
                that.map.clickCallback({
                    mapPoint: {
                        latitude: event.result.feature.geometry.latitude,
                        longitude: event.result.feature.geometry.longitude,
                    }
                });
            });
            that.map.arcgis.view.when(function(v) {
                v.popup.viewModel.on("trigger-action", function(event) {
                    if (event.action.id === "remove-pt") {
                        that.removeMarker(v.popup.viewModel.selectedFeature.attributes.delta);
                        that.removeInput(v.popup.viewModel.selectedFeature.attributes.delta);
                        v.popup.close();
                    }
                });
            });
        });
        return this;
    }

    GeolocationArcGISMapWidget.prototype = Object.create(Drupal.geolocation.widget.GeolocationMapWidgetBase.prototype);
    GeolocationArcGISMapWidget.prototype.constructor = GeolocationArcGISMapWidget;

    GeolocationArcGISMapWidget.prototype.addInput = function(location) {
        let d = this.getNextDelta(true);
        var input = this.getInputByDelta(d);
        if (input) {
            let loc = null;
            if (location.geometry) {
                loc = location.geometry;
            }
            else if (location.mapPoint) {
                loc = {
                    latitude: location.mapPoint.latitude,
                    longitude: location.mapPoint.longitude
                };
            }
            else {
                loc = {
                    latitude: location.lat,
                    longitude: location.lng
                };
            }
            input.find('input.geolocation-map-input-longitude').val(loc.longitude);
            input.find('input.geolocation-map-input-latitude').val(loc.latitude);
        }
    };

    GeolocationArcGISMapWidget.prototype.addMarker = function (location, delta) {
        let loc = null;
        if (location.geometry) {
            loc = location.geometry;
        }
        else if (location.mapPoint) {
            loc = {
                latitude: location.mapPoint.latitude,
                longitude: location.mapPoint.longitude
            };
        }
        else {
            loc = {
                latitude: location.lat,
                longitude: location.lng
            };
        }
        var marker = this.map.setMapMarker({
            popupTemplate: {
                content: "<strong>Latitude:</strong>&nbsp;{LAT}<br /><strong>Longitude:</strong>&nbsp;{LNG}",
                actions: [{
                    id: "remove-pt",
                    className: "fa fa-trash-o",
                    title: "Remove"
                }]
            },
            geometry: loc,
            goTo: true,
            attributes: {
                delta: delta,
                geometry: loc
            }
        });
        this.updateInput({ lng: loc.longitude, lat: loc.latitude }, delta);
        return marker;
    };

    GeolocationArcGISMapWidget.prototype.updateMarker = function (location, delta) {
        Drupal.geolocation.widget.GeolocationMapWidgetBase.prototype.updateMarker.call(this, delta);
        let marker = this.getMarkerByDelta(delta);
        marker.geometry.latitude = location.lat;
        marker.geometry.longitude = location.lng;
        this.locationModifiedCallback(location, delta);
        return marker;
    };

    GeolocationArcGISMapWidget.prototype.removeMarker = function(delta) {
        let marker = this.getMarkerByDelta(delta);
        if (marker) {
            this.map.removeMarker(marker);
            this.locationRemovedCallback(delta);
        }
    };

    GeolocationArcGISMapWidget.prototype.getNextDelta = function(current) {
        let reset = this.cardinality !== -1 ? this.cardinality : $(this.wrapper).find(':input.geolocation-map-input-latitude').length;
        let d = this.currentDelta;
        if (!!current) {
            return d;
        }
        if (d + 1 >= reset) {
            d = 0;
        }
        else {
            d++;
        }
        this.currentDelta = d;
        return this.currentDelta;
    };

    GeolocationArcGISMapWidget.prototype.getMarkerByDelta = function(delta) {
        return this.map.getMarkerByDelta(delta);
    }

    Drupal.geolocation.widget.GeolocationArcGISMapWidget = GeolocationArcGISMapWidget;
    Drupal.geolocation.widget.addWidgetProvider('arcgis', 'GeolocationArcGISMapWidget');
  
})(jQuery, Drupal);
  