/**
 * @file
 * Attaches behaviors for the custom Google Maps.
 */
(function ($, Drupal) {
    Drupal.behaviors.customMapBehavior = {
	attach: function (context, settings) {

	    $.fn.redirectCheckoutForm = function(data) {
		var postal_code = data.commerce_checkout_pane.container.postal_code;
		var city = data.commerce_checkout_pane.container.city;
		var country_code = data.commerce_checkout_pane.container.country;
		var current_url = data.commerce_checkout_pane.container.current_url;

		var url =  current_url + '?postal_code=' + postal_code + '&city=' + city + '&country=' + country_code;
		$(location).attr("href", url);
	    };

	    /**
	     * Initializes the map.
	     */
	    function init (offices, icon_marker) {
		var point = {lat: offices[0].lat, lng: offices[0].lon};

		var map = new google.maps.Map(document.getElementById('colissimo-map'), {
		    center: point,
		    scrollwheel: true, // by default is true.
		    zoom: 14
		});

		setMarkers(map, offices, icon_marker);
	    }

	    function setMarkers(map, offices, icon_marker) {
		// Adds markers to the map.
		// Marker sizes are expressed as a Size of X,Y where the origin of the image
		// (0,0) is located in the top left of the image.
		// Origins, anchor positions and coordinates of the marker increase in the X
		// direction to the right and in the Y direction down.

		var marker, i
		var image = {
		    url: icon_marker,
		    // This marker is 20 pixels wide by 32 pixels high.
		    size: new google.maps.Size(25, 32),
		    // The origin for this image is (0, 0).
		    origin: new google.maps.Point(0, 0),
		    // The anchor for this image is the base of the flagpole at (0, 32).
		    anchor: new google.maps.Point(0, 32)
		};

		// Shapes define the clickable region of the icon. The type defines an HTML
		// <area> element 'poly' which traces out a polygon as a series of X,Y points.
		// The final coordinate closes the poly by connecting to the first coordinate.
		var shape = {
		    coords: [1, 1, 1, 20, 18, 20, 18, 1],
		    type: 'poly'
		};

		for (i=0; i < offices.length; i++) {
		    var office = offices[i];

		    var marker = new google.maps.Marker({
			position: {lat: office.lat, lng: office.lon},
			map: map,
			icon: image,
			shape: shape,
			title: office.name,
			zIndex: i
		    });

		    map.setCenter(marker.getPosition())

		    var content =
			'<div id="content">'+
			'<h3 id="firstHeading" class="firstHeading"><b>' + office.name + '</b></h3>'+
			'<div id="bodyContent">'+
			'<span>' + office.adress + '</span><br>' +
			'<span>' + office.codePostal + ' ' + office.city + '</span><br>' +
			'<span>' + office.country + '</span><br>' +
			'</div>'+
			'</div>';
		    var infowindow = new google.maps.InfoWindow();

		    google.maps.event.addListener(marker, 'click', (function(marker, content, infowindow){
			return function() {
			    infowindow.setContent(content);
			    infowindow.open(map,marker);
			};
		    })(marker,content,infowindow));

		}
	    }

	    init(settings.offices, settings.icon_marker);

	}
    };

})(jQuery, Drupal);
