"use strict";

(function ($, Drupal) {
  Drupal.behaviors.sharethis_ajax = {
    attach: function () {
      // Ensures sharethis is initialised on both a normal page request and
      // an ajax loaded one.
      if (window.__sharethis__) {
        window.__sharethis__.initialize();
      }
    }
  };
}(jQuery, Drupal));
