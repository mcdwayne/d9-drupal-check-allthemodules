/**
 * @file
 * Autoban rules form behaviors.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.autobanForm = {
    attach: function () {

      // Set Type field value from desctription item.
      $('#edit-type--description span').click( function() {
        var text = $(this).text();
        $('#edit-type').val(text);
      });

    }
  };

}(jQuery, Drupal));
