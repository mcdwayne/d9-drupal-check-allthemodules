/**
 * @file
 * Javascript for Gmap polygon field.
 */

(function ($) {
  'use strict';

  Drupal.behaviors.gmap_polygon_field = {
    attach: function (context, settings) {

      // Add function to get Polygon bounds.
      function get_poly_bounds(poly) {
        var bounds = new google.maps.LatLngBounds();
        var paths = poly.getPaths();
        var path;
        for (var i = 0; i < paths.getLength(); i++) {
          path = paths.getAt(i);
          for (var ii = 0; ii < path.getLength(); ii++) {
            bounds.extend(path.getAt(ii));
          }
        }
        return bounds;
      }

      $('.gmap_polygon_field_map').each(function () {
        if($(this).hasClass('gmap-initialized')) return;
        var poly_input = $($(this).parent().find('input.gmap_polygon_field.form-text')[0]);

        var change_poly_text;
        var poly_edited;
        var poly_point_added;
        var set_listeners_for_polygon_path;
        var removeVertex;

        var map = new google.maps.Map(this, {
          center: {lat: 50.08804, lng: 14.42076},
          zoom: 11,
          disableDoubleClickZoom: true,
        });

        $(this).addClass('gmap-initialized');

        // Detect that we are in view only mode.
        var editable = $(this).hasClass('gmap-editable');

        var settings = {
          strokeColor: '#000000',
          strokeOpacity: 1.0,
          strokeWeight: 3,
          fillColor: '#000000',
        };
        if(drupalSettings.gmap_polygon_field['strokeColor'].length == 7 && drupalSettings.gmap_polygon_field['strokeColor'][0] == '#')
          settings.strokeColor = drupalSettings.gmap_polygon_field['strokeColor'];
        if(drupalSettings.gmap_polygon_field['fillColor'].length == 7 && drupalSettings.gmap_polygon_field['fillColor'][0] == '#')
          settings.fillColor = drupalSettings.gmap_polygon_field['fillColor'];
        if(!isNaN(parseFloat(drupalSettings.gmap_polygon_field['strokeOpacity'])))
          settings.strokeOpacity = parseFloat(drupalSettings.gmap_polygon_field['strokeOpacity']);
        if(!isNaN(parseFloat(drupalSettings.gmap_polygon_field['strokeWeight'])))
          settings.strokeWeight = parseFloat(drupalSettings.gmap_polygon_field['strokeWeight']);

        var poly = new google.maps.Polygon({
          strokeColor: settings['strokeColor'],
          strokeOpacity: settings['strokeOpacity'],
          strokeWeight: settings['strokeWeight'],
          fillColor: settings['fillColor'],
          map: map,
          editable: editable
        });

        if (editable) {
          if (poly_input.val()) {
            poly.setPath(google.maps.geometry.encoding.decodePath(poly_input.val()));
          }

          change_poly_text = function (path) {
            var encode_string = google.maps.geometry.encoding.encodePath(path);
            if (encode_string) {
              poly_input.val(encode_string);
            }
          };

          poly_edited = function (event) {
            var path = poly.getPath();
            change_poly_text(path);
          };

          poly_point_added = function (event) {
            var path = poly.getPath();
            path.push(event.latLng);
            change_poly_text(path);
          };

          // Remove vertex from polygon.
          removeVertex =  function (event) {
            var path = poly.getPath(),
              vertex = event.vertex;

            if (!path || vertex == undefined) {
              return;
            }

            path.removeAt(vertex);
          };

          // Event for dragging polygon is not necessary.
          // google.maps.event.addListener(poly, "dragend", poly_edited);
          // Listeners for events that changing poly path.
          set_listeners_for_polygon_path = function (p) {
            google.maps.event.addListener(p.getPath(), 'insert_at', poly_edited);
            google.maps.event.addListener(p.getPath(), 'remove_at', poly_edited);
            google.maps.event.addListener(p.getPath(), 'set_at', poly_edited);
          };

          set_listeners_for_polygon_path(poly);

          // Clicking on the map adds polygon point.
          google.maps.event.addListener(map, 'click', poly_point_added);

          // Remove vertex clicking on the right mouse button.
          google.maps.event.addListener(poly, 'rightclick', removeVertex);

          // Edit polygon on input change.
          poly_input.bind('input', function () {
            poly.setPath(google.maps.geometry.encoding.decodePath($(this).val()));
            // We overwrote polygon path - we have to set events again.
            set_listeners_for_polygon_path(poly);
            // Show whole polygon.
            map.fitBounds(get_poly_bounds(poly));
          });

        }
        else {
          var poly_data = $(this).attr('data-polyline-encoded');
          if (poly_data) {
            poly.setPath(google.maps.geometry.encoding.decodePath(poly_data));
          }
        }

        // Zoom map to fit whole polygon.
        map.fitBounds(get_poly_bounds(poly));
      });
    }
  };
})(jQuery);
