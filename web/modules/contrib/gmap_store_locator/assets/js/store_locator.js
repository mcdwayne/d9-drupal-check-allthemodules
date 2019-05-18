/**
 * @file
 * Contains the definition of the behaviour storeLocatorMap.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  /**
   * Attaches the Store Locator Behaviour.
   */
  Drupal.behaviors.storeLocatorMap = {
    attach: function (context, settings) {
      // Initialize map on Entity View mode
      if (typeof drupalSettings.store_locator != 'undefined') {
        var lt = drupalSettings.store_locator.latlng.lat;
        var lg = drupalSettings.store_locator.latlng.lng;
        initMap(lt, lg);
      }
      // Initialize map on Store Locator Page
      if (typeof drupalSettings.locator != 'undefined') {
        var data = drupalSettings.locator;
        initStoreLocatorMap(data);
      }


    }
  };
})(jQuery, Drupal, drupalSettings);

  /**
   * Add Javascript when DOM is ready.
   */
jQuery(document).ready(function () {
  'use strict';
  var lt = jQuery("input[name='latitude[0][value]']").val();
  var lg = jQuery("input[name='longitude[0][value]']").val();
  if ((typeof lt != 'undefined' && lt.length > 0) && (typeof lg != 'undefined' && lg.length > 0)) {
    initMap(parseFloat(lt), parseFloat(lg));
  }
  else {
    var ltlg = jQuery("input[name='default_map']").val();
    if (typeof ltlg != 'undefined' && ltlg.length > 0) {
      ltlg = ltlg.split(',');
      initMap(parseFloat(ltlg[0]), parseFloat(ltlg[1]));
    }
  }
});

// Initialize map on Entity Add/Edit mode
jQuery.fn.init_map = function (lt, lg) {
  'use strict';
  initMap(parseFloat(lt), parseFloat(lg));
};

  /**
   * Initialize location map & list.
   *
   * @param {Object} data
   *   InfoWindow Data.
   * @param {Url} marker_icon
   *   Custom Marker Icon.
   */
function initStoreLocatorMap(data) {
  'use strict';
  var markers = [];
  var map;
  var gmarker;
  var content;
  var bounds = new google.maps.LatLngBounds();

  var mapOptions = {
    mapTypeId: 'roadmap'
  };

  map = new google.maps.Map(document.getElementById('map'),
      mapOptions);
  map.setTilt(45);
  var infoWindow = new google.maps.InfoWindow();
  jQuery(this);
  jQuery.each(
      data.itemlist,

      function (index, marker) {
        var position = new google.maps.LatLng(
        marker.latitude, marker.longitude);
        bounds.extend(position);
        gmarker = new google.maps.Marker({
          position: position,
          map: map,
          id: index,
          title: marker.name,
          icon: data.marker.icon,
          animation: google.maps.Animation.DROP
        });

        google.maps.event.addListener(
        gmarker,
          'click', (function (gmarker, index) {
            return function () {
              content = '';
              jQuery.each(
            marker,

            function (
            key,
            value) {
              if (value !== null && key !== 'latitude' && key !== 'longitude') {

                if (key === 'website') {
                  var web = '<a href="' + value + '" target="_blank">' + value + '</a>';
                  content += '<div class="loc-' + key + '">' + web + '</div>';
                }
                else if (key === 'get_direction') {
                  if (data.get_direction === 1) {
                    content += '<div class="loc-' + key + '">' + value + '</div>';
                  }
                }
                else {
                  content += '<div class="loc-' + key + '">' + value + '</div>';
                }
              }
            });

              infoWindow.setContent(content);
              infoWindow.open(map, gmarker);
              if (!jQuery('.bh-sl-map').hasClass('block-map-view')) {
                jQuery(
              '.list-wrapper li')
              .removeClass(
              'highlight');
                jQuery(
              '.list-wrapper li')
              .eq(index)
              .addClass(
              'highlight');

                var container = jQuery('#location-list-wrapper');
                var scrollTo = jQuery(
                '.list-wrapper li')
                .eq(index);

                container.animate({
                  scrollTop: scrollTo.offset().top - container.offset().top + container.scrollTop()
                }, 1500);
              }
            };
          })(gmarker, index));
        markers.push(gmarker);
        map.fitBounds(bounds);
      });

  jQuery('.list-marker-id').on(
        'click',

      function (event) {
        event.preventDefault();
        google.maps.event.trigger(markers[jQuery(this).data(
          'markerid')], 'click');
      });

  jQuery('#search-location').on('keyup', function () {
    var value = jQuery(this).val();
    jQuery('.list-wrapper li').each(function () {
      if (jQuery(this).text().search(new RegExp(value, 'i')) > -1) {
        jQuery(this).show();
      }
      else {
        jQuery(this).hide();
      }
    });
  });
}

  /**
   * Initialize map based on Latitude & Longitude.
   *
   * @param {float} lt
   *   Latitude.
   * @param {float} lg
   *   Longitude.
   */
function initMap(lt, lg) {
  'use strict';
  var latlng = {
    lat: lt,
    lng: lg
  };
  var map = new google.maps.Map(document.getElementById('map'), {
    center: latlng,
    zoom: 18,
    mapTypeId: google.maps.MapTypeId.ROADMAP
  });

  var marker = new google.maps.Marker({
    position: latlng,
    map: map
  });

  marker.addListener('click', function () {
    infowindow.open(map, marker);
  });

  google.maps.event.addListener(map, 'click', function (event) {
    jQuery("input[name='latitude[0][value]']").val(event.latLng.lat());
    jQuery("input[name='longitude[0][value]']").val(event.latLng.lng());
    placeMarker(event.latLng, map);
  });

}

/**
   * Place Marker on Click on Map.
   *
   * @param {Object} position
   *   Marker position in Map.
   * @param {Object} map
   *   map Object.
   */
function placeMarker(position, map) {
  'use strict';
  var marker = new google.maps.Marker({
    position: position,
    map: map
  });
  map.panTo(position, marker);
}
