/**
 * Attach functionality for modifying map markers.
 */
(function ($, Drupal, drupalSettings) {
    Drupal.behaviors.leaflet_widget = {
        attach: function (context, settings) {
            $.each(settings.leaflet_widget, function (map_id, widgetSettings) {
                $('#' + map_id, context).each(function () {
                    var map = $(this);
                    // If the attached context contains any leaflet maps with widgets, make sure we have a
                    // Drupal.leaflet_widget object.
                    if (map.data('leaflet_widget') == undefined) {
                        var lMap = drupalSettings.leaflet[map_id].lMap;
                        map.data('leaflet_widget', new Drupal.leaflet_widget(map, lMap, widgetSettings));
                    }
                    else {
                        // If we already had a widget, update map to make sure that WKT and map are synchronized.
                        map.data('leaflet_widget').update_map();
                        map.data('leaflet_widget').update_input_state();
                    }

                });
            });

        }
    };

    Drupal.leaflet_widget = function (map_container, lMap, widgetSettings) {

        // A FeatureGroup is required to store editable layers
        this.drawnItems = new L.FeatureGroup();
        this.settings = widgetSettings;
        this.container = $(map_container).parent();
        this.json_selector = this.settings.jsonElement;
        this.layers = [];

        this.map = undefined;
        this.set_leaflet_map(lMap);
        // If map is initialised (or re-initialised) then use the new instance.
        this.container.on('leaflet.map', $.proxy(function (event, _m, lMap) {
            this.set_leaflet_map(lMap);
        }, this));

        // Update map whenever the input field is changed.
        this.container.on('change', this.json_selector, $.proxy(this.update_map, this));

        // Show, hide, mark read-only.
        this.update_input_state();
    };

    /**
     * Set the leaflet map object.
     */
    Drupal.leaflet_widget.prototype.set_leaflet_map = function (map) {
        if (map != undefined) {
            this.map = map;

            map.addLayer(this.drawnItems);

            if(this.settings.scrollZoomEnabled) {
                map.on('focus', function () {
                    map.scrollWheelZoom.enable();
                });
                map.on('blur', function () {
                    map.scrollWheelZoom.disable();
                });
            }

            map.pm.addControls(this.settings.toolbarSettings);

            map.on('pm:create', function(event){
                var layer = event.layer;
                this.drawnItems.addLayer(layer);
                this.update_text();

                // listen to changes on the new layer
                layer.on('pm:edit', function(event) {
                    this.update_text();
                }, this);

                // listen to drags on the new layer
                layer.on('pm:dragend', function(event) {
                    this.update_text();
                }, this);

            }, this);

            // listen to removal of layer.
            map.on('pm:remove', function(event) {
                this.drawnItems.removeLayer(event.layer);
                this.update_text();
            }, this);

            // listen to cuts on the new layer
            map.on('pm:cut', function (event) {
                // Cutting a layer return a new layer. The old layer has to be removed
                this.drawnItems.removeLayer(event.originalLayer);
                this.drawnItems.addLayer(event.layer);
                this.update_text();
            }, this);

            this.update_map();
        }
    };

    /**
     * Update the WKT text input field.
     */
    Drupal.leaflet_widget.prototype.update_text = function () {
        if (this.drawnItems.getLayers().length == 0) {
            $(this.json_selector, this.container).val('');
        }
        else {
            var json_string = JSON.stringify(this.drawnItems.toGeoJSON());
            $(this.json_selector, this.container).val(json_string);
        }
    };

    /**
     * Set visibility and readonly attribute of the input element.
     */
    Drupal.leaflet_widget.prototype.update_input_state = function () {
        $('.form-item.form-type-textarea', this.container).toggle(!this.settings.inputHidden);
        $(this.json_selector, this.container).prop('readonly', this.settings.inputReadonly);
    };

    /**
     * Update the leaflet map from text.
     */
    Drupal.leaflet_widget.prototype.update_map = function () {
        var value = $(this.json_selector, this.container).val();

        // Nothing to do if we don't have any data.
        if (value.length == 0) {

            // If no layer available, locate the user position.
            if(this.settings.locate) {
                this.map.locate({setView: true, maxZoom: 18});
            }

            this.drawnItems.eachLayer(function(layer) {
                this.map.removeLayer(layer);
            }, this);
            return;
        }

        try {
            var obj = L.geoJson(JSON.parse(value));
        } catch (error) {
            if (window.console) console.error(error.message);
            return;
        }

        this.drawnItems.eachLayer(function(layer) {
            this.map.removeLayer(layer);
        }, this);

        // See https://github.com/Leaflet/Leaflet.draw/issues/398
        obj.eachLayer(function(layer) {

            // listen to changes on the new layer
            layer.on('pm:edit', function(event) {
                this.update_text();
            }, this);

            // listen to drags on the new layer
            layer.on('pm:dragend', function(event) {
                this.update_text();
            }, this);

            this.drawnItems.addLayer(layer);
            //layer.addTo(this.drawnItems);
        }, this);

        // Pan the map to the feature
        if (this.settings.autoCenter) {
            if (obj.getBounds !== undefined && typeof obj.getBounds === 'function') {
                // For objects that have defined bounds or a way to get them
                this.map.fitBounds(obj.getBounds());
            } else if (obj.getLatLng !== undefined && typeof obj.getLatLng === 'function') {
                this.map.panTo(obj.getLatLng());
            }
        }
    };

})(jQuery, Drupal, drupalSettings);
