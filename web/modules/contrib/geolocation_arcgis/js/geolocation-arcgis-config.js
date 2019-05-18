(function($, Drupal) {
    Drupal.behaviors.geolocationArcGGISConfigFormGeocoder = {
        attach: function (context, settings) {
            $('[data-map-type="arcgis_maps"]', context).once().each(function() {
                let id = $(this).attr('id');
                let map = null;
                $.each(Drupal.geolocation.maps, function(idx, item) {
                    if (item.id == id) {
                        map = item;
                    }
                });
                if (map.ready) {
                    attachHandler(id, map);
                }
                else {
                    map.addReadyCallback(function() {
                        attachHandler(id, map);
                    })
                }
            });

            function attachHandler(id, map) {
                map.addSearchCallback(function(event) {
                    $('#' + id + '-details').val(event.result.name);
                });
                map.addClickCallback(function(event) {
                    if (map.homeMarker) {
                        map.removeMarker(map.homeMarker);
                    }
                    map.arcgis.searchWidget.clear();
                    map.homeMarker = map.setMapMarker({
                        geometry: {
                            type: 'point',
                            latitude: event.mapPoint.latitude,
                            longitude: event.mapPoint.longitude
                        },
                        goTo: true
                    });
                    $('#' + id + '-details').val('coords:' + event.mapPoint.longitude + ',' + event.mapPoint.latitude);
                });
                $(':input[name="map_type"]').change(function() {
                    map.arcgis.map.basemap = $(this).val();
                });
            }
        }
    };
})(jQuery, Drupal);
