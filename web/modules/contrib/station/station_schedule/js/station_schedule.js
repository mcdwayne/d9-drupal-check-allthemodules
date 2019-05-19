(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.station_schedule = {
    attach: function () {
      $('[data-drupal-station-schedule-height]').each(function () {
        var $this = $(this);
        var height = $this.data('drupal-station-schedule-height');
        $this.height(height);
      });
    }
  };

}(jQuery, Drupal, drupalSettings));
