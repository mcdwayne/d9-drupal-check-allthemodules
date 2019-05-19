/**
 * @file
 */

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.event_extras_location = {
    attach: function (context, settings) {
      // Only fire once when page first loads.
      if (context === document) {
        var container = $("#event-location-map");
        container.addClass("ajax-progress-throbber").append("<div class=\"throbber\" style=\"padding-left: 18px;\">Loading</div>");

        var servicepath = 'https://extreme-ip-lookup.com/json/' + settings.wee.hostname;

        $.get(servicepath, function (data) {
          // Remove throbber.
          container.removeClass("ajax-progress-throbber");
          $(".throbber", container).remove();
          // Create table.
          var table = $("<table style=\"width: 100%\"></table>").addClass("geo-ip");
          var row = $("<tr><th>Service path</th><td>" + servicepath + "</td></tr>");
          table.append(row);

          $.each(data, function (index, value) {
            var row = $("<tr><th>" + index + "</th><td>" + value + "</td></tr>");
            table.append(row);
          });
          container.append(table);
          $("table tr:even", container).addClass("even");
          $("table tr:odd", container).addClass("odd");

          // Add map.
          var mapcontainer = container.append("<div id=\"map-container\" style=\"width:100%; height: 300px\"></div>");
          var watchdog_event_latlon = new google.maps.LatLng(data.lat, data.lon);
          var watchdog_event_map = new google.maps.Map(document.getElementById("map-container"), {
            center: watchdog_event_latlon,
            zoom: 13,
            mapTypeId: "roadmap"
          });
          var watchdog_event_marker = new google.maps.Marker({
            position: watchdog_event_latlon,
            map: watchdog_event_map,
          });
        });
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
