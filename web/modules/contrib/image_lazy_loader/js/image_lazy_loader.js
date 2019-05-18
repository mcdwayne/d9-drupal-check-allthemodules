/**
 * @file
 * Adds Lozad lazy-loading JS functionality.
 */

(function () {
  'use strict';
  Drupal.behaviors.image_lazy_loader = {
    attach: function (context) {
      if (typeof window.lozad === 'function') {
        Drupal.behaviors.image_lazy_loader.observer = lozad();
        Drupal.behaviors.image_lazy_loader.observer.observe();
      }
    }
  };
})(jQuery);
