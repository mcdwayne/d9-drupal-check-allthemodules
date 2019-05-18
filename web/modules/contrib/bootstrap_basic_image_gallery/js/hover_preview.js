/**
 * @file
 * Bootstrap Basic Image Gallery - hovering over thumbnails changes main.
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.bootstrap_basic_image_gallery_hover_preview = {
    attach: function attach(context, settings) {

      // Find each thumbnail.
      $.each($(context).find('.bootstrap-basic-image-gallery .thumb').once('hover_preview'), function (index, item) {
        // On hover, exchange the main image with the thumbnail image.
        $(item).on('mouseover', function(ev) {
          // Get the thumbnail being hovered.
          var thumbnailContainer = $(ev.currentTarget);
          // Find the main image div.
          var mainContainer = $(item).parent().parent().find('.main-image');
          // Set the source to be the source from the thumbnail.
          mainContainer.find('img').attr('src', thumbnailContainer.find('img').data('mainsrc'));
          // Set the slide-to.
          mainContainer.attr('data-slide-to', thumbnailContainer.attr('data-slide-to'));
        });
      });

    }
  };
})(jQuery, Drupal);
