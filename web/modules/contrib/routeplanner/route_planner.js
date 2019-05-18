(function ($, Drupal, drupalSettings) {
    Drupal.behaviors.route_planner = {
        attach: function (context, settings) {
                if (!myRoutePlanner) {
                    var myRoutePlanner = new Drupal.RoutePlanner();
                    $('#route-planner-address-form .form-submit').click(function(){
                        myRoutePlanner.calcRoute();
                    });
                }
        }
    };

    Drupal.RoutePlanner = function () {
        this.directionsService = new google.maps.DirectionsService();
        this.map = null;

        // initialize the map
        this.route();

    }

    Drupal.RoutePlanner.prototype.route = function () {

        directionsDisplay = new google.maps.DirectionsRenderer();
        geocoder = new google.maps.Geocoder();

        var latLng;
        if (geocoder) {
            var end = drupalSettings.route_planner.end;
            if(document.getElementById("edit-end")){
                var end = document.getElementById("edit-end").value;
            }
            geocoder.geocode({ "address": end}, function (results, status) {
                if (status == google.maps.GeocoderStatus.OK) {
                    latLng = new String(results[0].geometry.location);
                    latLng = latLng.substr(1, (latLng.length - 2));
                    latLng = latLng.split(",");
                    var location = new google.maps.LatLng(latLng[0], latLng[1]);
                    var myOptions = {
                        zoom: Number(drupalSettings.route_planner.zoomlevel),
                        scrollwheel: drupalSettings.route_planner.scrollwheel,
                        mapTypeControl: drupalSettings.route_planner.mapTypeControl,
                        scaleControl: drupalSettings.route_planner.scaleControl,
                        draggable: drupalSettings.route_planner.draggable,
                        zoomControl: drupalSettings.route_planner.zoomcontrol,
                        disableDoubleClickZoom: drupalSettings.route_planner.doubbleclick,
                        streetViewControl: drupalSettings.route_planner.streetviewcontrol,
                        overviewMapControl: drupalSettings.route_planner.overviewmapcontrol,
                        disableDefaultUI: drupalSettings.route_planner.defaultui,
                        mapTypeId: google.maps.MapTypeId.ROADMAP,
                        center: location
                    }
                    this.map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
                    var marker = new google.maps.Marker({
                        map: this.map,
                        position: results[0].geometry.location
                    });
                    directionsDisplay.setMap(this.map);
                }
                else {
                    alert(Drupal.t("Geocode was not successful for the following reason: ") + status);
                }
            });
        }
        return false;
    }

    Drupal.RoutePlanner.prototype.calcRoute = function () {
        var start = document.getElementById("edit-start").value;
        var end = drupalSettings.route_planner.end;
        if(document.getElementById("edit-end")){
            var end = document.getElementById("edit-end").value;
        }
        if(Number(drupalSettings.route_planner.unitSystem) == 1){
            var unit = google.maps.UnitSystem.IMPERIAL;
        }else{
            var unit = google.maps.UnitSystem.METRIC;
        }

        var request = {
            origin: start,
            destination: end,
            travelMode: google.maps.DirectionsTravelMode.DRIVING,
            unitSystem: unit
        };
        this.directionsService.route(request, function (response, status) {
            if (status == google.maps.DirectionsStatus.OK) {
                this.directionsDisplay.setDirections(response);
                distance = response.routes[0].legs[0].distance.text;
                time = response.routes[0].legs[0].duration.text;
                document.getElementById("edit-time").value = time;
                document.getElementById("edit-distance").value = distance;
            }
        });
        return false;
    }

})(jQuery, Drupal, drupalSettings);
