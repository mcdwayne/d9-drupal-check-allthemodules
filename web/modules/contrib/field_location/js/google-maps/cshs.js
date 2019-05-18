(function($, name) {
  'use strict';

  Drupal.behaviors[name] = {
    attach: function () {
      var $elements = $('.simpler-select-root').nextAll('.select-wrapper').find('select');
      var deferred = $.Deferred().fail(function() {
        // Give user a chance to choose his location.
        $elements.prop('disabled', false);
      });

      // Disable editing.
      $elements.prop('disabled', true);

      navigator.geolocation.getCurrentPosition(function(position) {
        var me = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);

        $.each(drupalSettings[name], function(fieldName, items) {
          $.each(items, function(htmlID, terms) {
            $.each(terms, function(termID, coordinates) {
              if (google.maps.geometry.poly.containsLocation(me, new google.maps.Polygon({path: JSON.parse(coordinates)}))) {
                $('#' + htmlID)
                  .val(termID)
                  .data('plugin_simplerSelect')
                  .init();

                deferred.resolve(me);

                return false;
              }
            });
          });
        });

        deferred.reject();
      });
    }
  };
})(jQuery, 'locationFieldsCshs');
