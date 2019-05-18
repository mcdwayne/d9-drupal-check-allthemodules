/**
 * @file
 */

(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.flot = {
    attach: function () {
      window.flotArray = [];
      var flot = drupalSettings.flot;
      $.each(flot, function (key, flot) {
        var div_id = key;
        var data = flot.data;
        var options = flot.options;
        if (typeof data !== 'undefined') {
          if (typeof options !== 'undefined' && options !== null) {
            if (typeof options.series !== 'undefined' && typeof options.series.images !== 'undefined' && typeof options.series.images.show !== 'undefined') {
              if (options.series.images.show === true) {
                $.plot.image.loadDataImages(data, options, function () {
                  $.plot('#' + div_id, data, options);
                });
              }
            }
            else {
              window.flotArray[div_id] = $.plot('#' + div_id, data, options);
            }
          }
          else {
            window.flotArray[div_id] = $.plot('#' + div_id, data);
          }
        }
      });
    }};
}(jQuery, Drupal, drupalSettings));
