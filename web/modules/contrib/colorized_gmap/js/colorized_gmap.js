(function ($, Drupal, drupaldrupalSettings) {
  Drupal.behaviors.ColorPickerfields = {
    attach: function (context, drupalSettings) {
      var blocks = drupalSettings['colorized_gmap'];
      var item;
      for (item in blocks) {
        var block = blocks[item];
        init(blocks[item], item);
      }

      function init(block, machine_name) {
        if (block['style'] == 'undefined') {
          var latitude = 48.853358;
          var longitude = 2.348903;
          var markertitle = 'Destination';
          var infowindowContent = '';
          var mapOptions = {
            zoom: 15,
            center: new google.maps.LatLng(latitude, longitude)
          };
        }
        else {
          var markericon = null;
          if (block['additional_settings']['marker_settings']['marker']['url']) {
            markericon = block['additional_settings']['marker_settings']['marker']['url'];
          }
          if (block['additional_settings']['marker_settings']['info_window']) {
            var infowindowContent = block['additional_settings']['marker_settings']['info_window']['value'];
          }
          var markertitle = block['additional_settings']['marker_settings']['markertitle'];
          var latitude = block['coordinates']['latitude'];
          var longitude = block['coordinates']['longitude'];
          var mapstyle = block['style'];
          var zoomControlSize = block['additional_settings']['zoom_controls']['zoomControlSize'];
          var zoomControlPosition = block['additional_settings']['zoom_controls']['zoomControlPosition'];
          var mapOptions = {
            zoom: parseInt(block['additional_settings']['zoom_controls']['zoom']),
            center: new google.maps.LatLng(latitude, longitude),
            styles: mapstyle,
            scrollwheel: block['additional_settings']['zoom_controls']['scrollwheel'],
            streetViewControl: block['additional_settings']['controls']['streetViewControl'],
            streetViewControlOptions: {
              position: google.maps.ControlPosition = block['additional_settings']['controls_position']['streetViewControlPosition']
            },
            mapTypeControl: block['additional_settings']['controls']['mapTypeControl'],
            mapTypeControlOptions: {
              position: google.maps.ControlPosition = block['additional_settings']['controls_position']['mapTypeControlPosition'],
            },
            zoomControl: block['additional_settings']['zoom_controls']['zoomControl'],
            draggable: true,
            panControl: block['additional_settings']['controls']['panControl'],
            panControlOptions: {
              position: google.maps.ControlPosition = block['additional_settings']['controls_position']['panControlPosition']
            },
            zoomControlOptions: {
              style: google.maps.ZoomControlStyle = zoomControlSize,
              position: google.maps.ControlPosition = zoomControlPosition
            }
          };
          var mindragwidth = block['additional_settings']['controls']['min_drag_width'];
          if ($(document).width() < mindragwidth && mindragwidth != 0) {
            mapOptions.draggable = false;
          }
        }
        var mapId = 'colorized-gmap-' + block['machine_name'];

        var mapElement = document.getElementById(mapId);
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
        if(block['additional_settings']['marker_settings']['displayPopupContent']){
          marker.infowindow.open(map, marker);
        }

        google.maps.event.addListener(marker, 'click', function () {
          if (this.infowindow) {
            this.infowindow.open(map, this);
          }
        });

      }
    }
  };

})(jQuery, Drupal);
