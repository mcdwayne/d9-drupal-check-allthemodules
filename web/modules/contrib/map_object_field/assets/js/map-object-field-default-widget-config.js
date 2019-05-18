/**
 * @file
 */

var default_widget_settings_map;
(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.map_object_field_default_widget_config = {
    attach: function (context) {
      if (typeof google !== 'undefined') {
        $('.map_default_widget_settings_map', context).each(function () {
          var lat = $('#map_dws_lat', context).val();
          var lng = $('#map_dws_lng', context).val();
          var zoom = parseInt($('#map_dws_zoom', context).val());
          var mapType = $('#map_dws_map_type input:checked', context).val();

          var latlng = new google.maps.LatLng(lat, lng);
          var mapOptions = {
            zoom: zoom,
            center: latlng,
            streetViewControl: false,
            mapTypeId: google.maps.MapTypeId[mapType.toLocaleUpperCase()]
          };

          default_widget_settings_map = new google.maps.Map(this, mapOptions);

          default_widget_settings_map.addListener('zoom_changed', function () {
            $('#map_dws_zoom').val(default_widget_settings_map.zoom);
          });

          default_widget_settings_map.addListener('center_changed', function () {
            $('#map_dws_lat').val(default_widget_settings_map.center.lat());
            $('#map_dws_lng').val(default_widget_settings_map.center.lng());
          });

          default_widget_settings_map.addListener('maptypeid_changed', function () {
            $('#map_dws_map_type input[value="' + default_widget_settings_map.mapTypeId + '"]').prop('checked', true);
          });
        });
      }
    }
  };
})(jQuery, Drupal);
