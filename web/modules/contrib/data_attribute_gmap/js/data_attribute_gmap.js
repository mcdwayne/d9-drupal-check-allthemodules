/**
 * Generates a google map for elements with a data-address attribute
 *
 * Examples of elements that will be converted to a google map:
 * <div data-address="zavelheide 15, 2200 herentals">
 * <div data-address="zavelheide 15, 2200 herentals" data-latitude="51.265447" data-longitude="3.3555155">
 *
 * Map settings can be found on admin/config/data_attribute_gmap/config
 * - Google Maps API key
 * - Default map width, height and zoom level
 * - Custom marker
 * - Custom map style
 * - Various general google maps settings and behaviours
 *
 * Created by: joery.lemmens@intracto.com
 * Created on: 29/05/2018
 */
(function ($, Drupal) {
    Drupal.behaviors.dataAttributeGmap = {

        /**
         * Set google maps element dimensions.
         *
         * Give the element a width and height if it doesnt have any,
         * because otherwise map will not be visible.
         *
         * @param {jQuery.Element} mapElement
         *   The google map element.
         */
        setDimensions: function(mapElement) {
            if(mapElement.width() === 0 || mapElement.width() < 100){
                mapElement.width(drupalSettings.data_attribute_gmap.gmap.width);
            }
            if(mapElement.height() === 0 || mapElement.height() < 100){
                mapElement.height(drupalSettings.data_attribute_gmap.gmap.height);
            }
        },

        setMarkers: function(mapElement, map, markerIcon, popupcontent, mapitems, bounds, allMarkers, allInfoWindows) {
            /**
             * If latitude and longitude were provided via data attributes
             * on the map element, use those to set the map position and show
             * the marker (dont geocode in this case).
             */
            if(mapElement.data('latitude') && mapElement.data('longitude')){
                var position = new google.maps.LatLng(mapElement.data('latitude'), mapElement.data('longitude'));
                Drupal.behaviors.dataAttributeGmap.setMarker(position, map, markerIcon, mapElement, popupcontent, bounds, allMarkers, allInfoWindows);

            }

            /**
             * If no latitude and longitude are provided but an address is provided on the map element:
             * geocode the address string to coordinates and center the map and marker
             * on those coordinates.
             */
            if($(this).data('address') && (!$(this).data('latitude') || !$(this).data('longitude'))){
                Drupal.behaviors.dataAttributeGmap.geocodeAddress($(this), map, markerIcon, $(this).html(), bounds, allMarkers, allInfoWindows);
            }

            /**
             * Create multiple markers.
             *
             * Example structure inside the js-gmap div:
             <div class="js-gmap__item" data-latitude="51.0743623" data-longitude="3.6645769">
             EN Handelspand te koop in Gent2, €2.250.000
             </div>
             <div class="js-gmap__item" data-latitude="51.17870797" data-longitude="3.4517700500001">
             Magazijn met woonst met 4 slaapkamers te Maldegem. T8000-Q0549a
             </div>
             <div class="js-gmap__item" data-latitude="50.94526262" data-longitude="3.12681587">
             Kantoor-/praktijkruimte in centrum Roeselare T8800-17007
             </div>
             */
            if (mapitems.length) {

                mapitems.each(function (index) {
                    // Check if marker has latitude and longitude.
                    var lat = $(this).data('latitude');
                    var long = $(this).data('longitude');
                    var address = $(this).data('address');

                    // Set marker using latitude and longitude data.
                    if (lat && long) {
                        var position = new google.maps.LatLng($(this).data('latitude'), $(this).data('longitude'));
                        Drupal.behaviors.dataAttributeGmap.setMarker(position, map, markerIcon, $(this), $(this).html(), bounds, allMarkers, allInfoWindows);
                    }

                    // Set marker using address string (geocode).
                    if (address && (!lat || !long)) {
                        Drupal.behaviors.dataAttributeGmap.geocodeAddress($(this), map, markerIcon, $(this).html(), bounds, allMarkers, allInfoWindows);
                    }

                });
            }

            // Multiple markers ? work with bounds to auto zoom and center the map.
            // If it is a single marker, position is set within setMarker and geocodeAddress.
            // If there is only one marker, center map on that marker
            var i = 0;
            allMarkers.forEach(function(item) {
                i++;
            });
            if (i > 1) {
                map.fitBounds(bounds);
            }

        },

        initMap: function(mapElement, index, settings) {
            // Generate a new google map object.
            var map = new google.maps.Map($('.js-gmap')[index], settings);

            // Add a loaded class to the map element when the map is fully loaded.
            google.maps.event.addListener(map, 'tilesloaded', function() {
                mapElement.addClass('is-loaded');
            });

            return map;
        },

        /**
         * custom function to convert address string to latitude and longitude.
         *
         * @param el    The html element that will be turned into a Google Map.
         * @param map   The current google.maps map object.
         * @param markerIcon    False or a path to a custom marker icon.
         * @param popupcontent  Empty or html to be used for the marker popup.
         */
        geocodeAddress: function(el, map, markerIcon, popupcontent, bounds, allMarkers, allInfoWindows) {
            var geocoder = new google.maps.Geocoder();
            geocoder.geocode({'address': el.data('address')}, function(results, status) {
                if (status === google.maps.GeocoderStatus.OK) {
                    //map.setCenter(results[0].geometry.location);
                    bounds.extend(results[0].geometry.location);
                    Drupal.behaviors.dataAttributeGmap.setMarker(results[0].geometry.location, map, markerIcon, el, popupcontent, bounds, allMarkers, allInfoWindows)
                } else {
                    console.log('Google maps could not geocode (convert address into latitude and longitude) for this reason: ' + status);
                }
            });
        },

        /**
         * Custom function to show a marker on the map.
         *
         * @param position      A google.maps.LatLng object with the position.
         * @param map           The current google.maps map object.
         * @param markerIcon    False or a path to a custom marker icon.
         * @param el            The html element that contains the necessary data for creating a marker.
         * @param popupcontent  HTML content that should be displayed inside the popup if you want one.
         */
        setMarker: function(position, map, markerIcon, el, popupcontent, bounds, allMarkers, allInfoWindows) {
            var marker = new google.maps.Marker({
                position: position,
                map: map,
                title: el.attr('data-markertitle'),
                animation: google.maps.Animation.DROP
            });

            bounds.extend(position);

            if(markerIcon){
                marker.setIcon(markerIcon);
            }
            if (el.attr('data-marker')) {
                marker.setIcon(el.attr('data-marker'));
            }

            /**
             * Generate popup when clicking on the marker.
             */
            var popup = '';

            // Get popup title from data-popuptitle attribute.
            if(el.attr('data-popuptitle')){
                popup += '<strong>' + el.attr('data-popuptitle') + '</strong>';
            }

            // Get popup content from data-popupcontent attribute.
            if(el.attr('data-popupcontent')){
                popup += '<p>' + el.attr('data-popupcontent') + '</p>';
            }

            // Get popup content from selector defined in data-popupcontent-target attribute.
            if(el.attr('data-popupcontent-target')){
                popup += $(el.attr('data-popupcontent-target')).html();
            }

            // Get popup content from js-gmap__popup element inside the js-gmap div.
            if (popupcontent) {
                popup += popupcontent;
            }

            // Trim whitespaces.
            popup = $.trim(popup);

            // Create infowindow, even if its empty so nr of infowindows = nr of markers.
            var infowindow = new google.maps.InfoWindow({
                content: popup
            });

            // Save reference to infowindow in array so it can be triggered outside this function.
            if (el.attr('data-markerid')) {
                allInfoWindows[el.attr('data-markerid')] = infowindow;
            } else {
                allInfoWindows.push(infowindow);
            }

            // But only show popup if infowindow is not empty.
            if(popup){

                marker.addListener('click', function() {
                    Drupal.behaviors.dataAttributeGmap.closeAllInfoWindows(allInfoWindows);
                    infowindow.open(map, marker);
                    // If popup styler module is present, execute it to remove default drupal popup styling.
                    if (Drupal.behaviors.gmapPopupStyler) {
                        Drupal.behaviors.gmapPopupStyler.addPopupClasses('small');
                    }

                });
                /*marker.addListener('mouseover', function() {
                 infowindow.open(map, marker);
                 // If popup styler module is present, execute it to remove default drupal popup styling.
                 Drupal.behaviors.gmapPopupStyler.addPopupClasses('small');
                 });
                 marker.addListener('mouseout', function() {
                 //infowindow.close();
                 Drupal.behaviors.dataAttributeGmap.closeAllInfoWindows(allInfoWindows);
                 });*/
            }

            // Save reference to marker in array so it can be triggered outside this function.
            if (el.attr('data-markerid')) {
                allMarkers[el.attr('data-markerid')] = marker;
            } else {
                allMarkers.push(marker);
            }

            // If there is only one marker, center map on that marker
            var i = 0;
            allMarkers.forEach(function(item) {
                i++;
            });
            if (i === 1) {
                map.setCenter(position);
            }

        },

        closeAllInfoWindows: function(infowindows) {
            $.each(infowindows, function(index, item){
                if (item) {
                    item.close();
                }
            });
        },

        /**
         * External controls:
         * If there is an element with class js-gmap__external-controls on the page,
         * then hovering on the first item inside that element will show the popup of the first marker etc.
         */
        externalControls: function(allInfoWindows, map, allMarkers) {
            var controls = $('.js-gmap__external-control');
            if (controls.length) {
                controls.on({
                    mouseenter: function () {
                        if ($(this).attr('data-markerid')) {
                            var infowindow = allInfoWindows[$(this).attr('data-markerid')];
                            if (infowindow) {
                                infowindow.open(map, allMarkers[$(this).attr('data-markerid')]);
                            }
                        } else {
                            allInfoWindows[$(this).index()].open(map, allMarkers[$(this).index()]);
                        }

                        // If popup styler module is present, execute it to remove default drupal popup styling.
                        Drupal.behaviors.gmapPopupStyler.addPopupClasses('small');
                    },
                    mouseout: function () {
                        Drupal.behaviors.dataAttributeGmap.closeAllInfoWindows(allInfoWindows);
                    }
                });
            }
        },

        init: function (context) {
            // Only do stuff if there is an actual element with js-gmap class present.
            if($('.js-gmap', context).length){

                /**
                 * Init variables and settings global for each google map element.
                 */
                var markerIcon = !drupalSettings.data_attribute_gmap.gmap.marker_path ? false : drupalSettings.data_attribute_gmap.gmap.marker_path;
                var settings = {
                    backgroundColor: drupalSettings.data_attribute_gmap.gmap.backgroundColor,
                    center: new google.maps.LatLng(drupalSettings.data_attribute_gmap.gmap.center_lat, drupalSettings.data_attribute_gmap.gmap.center_long),
                    clickableIcons: drupalSettings.data_attribute_gmap.gmap.clickableIcons,
                    disableDefaultUI: drupalSettings.data_attribute_gmap.gmap.disableDefaultUI,
                    disableDoubleClickZoom: drupalSettings.data_attribute_gmap.gmap.disableDoubleClickZoom,
                    draggable: drupalSettings.data_attribute_gmap.gmap.draggable,
                    fullscreenControl: drupalSettings.data_attribute_gmap.gmap.fullscreenControl,
                    keyboardShortcuts: drupalSettings.data_attribute_gmap.gmap.keyboardShortcuts,
                    mapTypeControl: drupalSettings.data_attribute_gmap.gmap.mapTypeControl,
                    mapTypeId: drupalSettings.data_attribute_gmap.gmap.mapTypeId,
                    panControl: drupalSettings.data_attribute_gmap.gmap.panControl,
                    rotateControl: drupalSettings.data_attribute_gmap.gmap.rotateControl,
                    scaleControl: drupalSettings.data_attribute_gmap.gmap.scaleControl,
                    scrollwheel: drupalSettings.data_attribute_gmap.gmap.scrollwheel,
                    streetViewControl: drupalSettings.data_attribute_gmap.gmap.streetViewControl,
                    styles: drupalSettings.data_attribute_gmap.gmap.styles ? JSON.parse(drupalSettings.data_attribute_gmap.gmap.styles) : '',
                    zoom: drupalSettings.data_attribute_gmap.gmap.zoom,
                    zoomControl: drupalSettings.data_attribute_gmap.gmap.zoomControl,
                    gestureHandling: 'cooperative'
                };

                /**
                 * Generate each single google map element (there can be multiple on a page).
                 */
                $('.js-gmap').each(function (index) {

                    /**
                     * Init variables per google map element.
                     */
                    var allMarkers = [];
                    var allInfoWindows = [];
                    var bounds = new google.maps.LatLngBounds();
                    // If the js-gmap div has tooltip content inside .js-gmap__popup, save it in a variable.
                    var popupcontent = $(this).children( '.js-gmap__popup' ).html();
                    // If the js-gmap div has subitems, save them in a variable.
                    var mapitems = $(this).children( '.js-gmap__item' );

                    // Set element default width and height (if not defined by css).
                    Drupal.behaviors.dataAttributeGmap.setDimensions($(this));

                    // Init a new map object (markers an positioning will happen later).
                    var map = Drupal.behaviors.dataAttributeGmap.initMap($(this), index, settings);

                    // Generate the markers for this map.
                    Drupal.behaviors.dataAttributeGmap.setMarkers($(this), map, markerIcon, popupcontent, mapitems, bounds, allMarkers, allInfoWindows);

                    // Generate external control functions.
                    Drupal.behaviors.dataAttributeGmap.externalControls(allInfoWindows, map, allMarkers);
                });
            }
        },

        attach: function (context) {
            waitUntilGoogleIsLoaded(context);
        }
    };

    function waitUntilGoogleIsLoaded(context){
        if (typeof(google) === 'undefined') {
            setTimeout(function() {
                waitUntilGoogleIsLoaded();
            }, 100)
        }
        if (typeof(google) !== 'undefined') {
            Drupal.behaviors.dataAttributeGmap.init(context);
        }
    }

})(jQuery, Drupal);
