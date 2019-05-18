(function ($, Drupal, drupaldrupalSettings) {

  Drupal.behaviors.ColorPickerfields = {
    attach: function (context, drupalSettings) {
      if ($('#gmap-ajax-wrapper tbody .form-type-textfield input.edit_color_input').length) {
        $('#gmap-ajax-wrapper tbody .form-type-textfield input.edit_color_input').ColorPicker({
          onSubmit: function(hsb, hex, rgb, el) {
            $(el).ColorPickerHide();
            $(el).val('#' + hex);
            $(el).trigger('textfield_change');
          },
          onBeforeShow: function () {
            $(this).ColorPickerSetColor(this.value);
          }
        });
      }
    }
  };

  Drupal.behaviors.ColorizedMap = {
    attach: function (context, drupalSettings) {
      var markertitle = 'Destination';
      var markericon = null;
      if (drupalSettings['colorized_gmap']['additional_settings']['marker_settings']['marker']['url']) {
        markericon = drupalSettings['colorized_gmap']['additional_settings']['marker_settings']['marker']['url'];
      }
      if (drupalSettings['colorized_gmap']['additional_settings']['marker_settings']['info_window']) {
        var infowindowContent = drupalSettings['colorized_gmap']['additional_settings']['marker_settings']['info_window']['value'];
      }
      var markertitle = drupalSettings['colorized_gmap']['additional_settings']['marker_settings']['markertitle'];
      var latitude = drupalSettings['colorized_gmap']['coordinates']['latitude'];
      var longitude = drupalSettings['colorized_gmap']['coordinates']['longitude'];
      var mapstyle = drupalSettings['colorized_gmap']['style'];
      var zoomControlSize = drupalSettings['colorized_gmap']['additional_settings']['zoom_controls']['zoomControlSize'];
      var zoomControlPosition = drupalSettings['colorized_gmap']['additional_settings']['zoom_controls']['zoomControlPosition'];
      var mapOptions = {
        zoom: parseInt(drupalSettings['colorized_gmap']['additional_settings']['zoom_controls']['zoom']),
        center: new google.maps.LatLng(latitude, longitude),
        styles: mapstyle,
        scrollwheel: drupalSettings['colorized_gmap']['additional_settings']['zoom_controls']['scrollwheel'],
        streetViewControl: drupalSettings['colorized_gmap']['additional_settings']['controls']['streetViewControl'],
        streetViewControlOptions: {
          position: google.maps.ControlPosition = drupalSettings['colorized_gmap']['additional_settings']['controls_position']['streetViewControlPosition']
        },
        mapTypeControl: drupalSettings['colorized_gmap']['additional_settings']['controls']['mapTypeControl'],
        mapTypeControlOptions: {
          position: google.maps.ControlPosition = drupalSettings['colorized_gmap']['additional_settings']['controls_position']['mapTypeControlPosition'],
        },
        zoomControl: drupalSettings['colorized_gmap']['additional_settings']['zoom_controls']['zoomControl'],
        draggable: true,
        panControl: drupalSettings['colorized_gmap']['additional_settings']['controls']['panControl'],
        panControlOptions: {
          position: google.maps.ControlPosition = drupalSettings['colorized_gmap']['additional_settings']['controls_position']['panControlPosition']
        },
        zoomControlOptions: {
          style: google.maps.ZoomControlStyle = zoomControlSize,
          position: google.maps.ControlPosition = zoomControlPosition
        }
      };

      var mapElement = document.getElementById('colorized-gmap-content');
      // Wait until all elements will be loaded.
      if (mapElement == null) {
        return;
      }
      var map = new google.maps.Map(mapElement, mapOptions);

      var markerOptions = {
        position: new google.maps.LatLng(latitude, longitude),
        map: map,
        title: markertitle,
        icon: markericon,
      };

      if (infowindowContent && infowindowContent != '') {
        var infowindow = new google.maps.InfoWindow({
          content: infowindowContent
        });
        markerOptions.infowindow = infowindow;
      }

      marker = new google.maps.Marker(markerOptions);

      google.maps.event.addListener(marker, 'click', function () {
        if (this.infowindow) {
          this.infowindow.open(map, this);
        }
      });
    }
  };

})(jQuery, Drupal);
