/**
 * @file
 * Bootstrap Basic Image Gallery - lazy load carousel images.
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.bootstrap_basic_image_gallery_lazyload = {
    attach: function attach(context, settings) {

      // Find each lazy modal.
      $.each($(context).find('.bootstrap-basic-image-gallery .modal.lazy').once('lazyload'), function (index, item) {
        // When the modal is shown, lazy load the active image.
        $(item).on('show.bs.modal', function(ev) {
          var slideToDelta = $(ev.relatedTarget).data('slide-to');
          var lazyElement = $(ev.currentTarget).find(".item.slide-" + slideToDelta + " img[data-src]");
          lazyElement.attr("src", lazyElement.data('src'));
          lazyElement.removeAttr("data-src");
        });
      });

      // Find each lazy carousel.
      $.each($(context).find('.bootstrap-basic-image-gallery .carousel.lazy').once('lazyload'), function (index, item) {
        // When the carousel slides, lazy load the image.
        $(item).on('slide.bs.carousel', function(ev) {
          var lazyElement = $(ev.relatedTarget).find("img[data-src]");
          lazyElement.attr("src", lazyElement.data('src'));
          lazyElement.removeAttr("data-src");
        });
      });

    }
  };
})(jQuery, Drupal);
