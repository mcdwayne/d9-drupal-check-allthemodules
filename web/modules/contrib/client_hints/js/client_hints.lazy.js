/**
 * @file scheduling.recurring.js
 *
 * Utility functions for recurring scheduling widget.
 */


(function ($, Drupal) {

  'use strict';

  /**
   * Registers behaviours related to entity reference field widget.
   */
  Drupal.behaviors.clientHintsLazy = {
    attach: function (context) {

      $(context).imagesLoaded( function() {

        $(this.images).each(function() {

          if (typeof this.img.dataset.clientHintsSrc !== 'undefined') {

            // Cache element
            var img = $(this.img);

            // Determine DPR
            var dpr = window.devicePixelRatio || 1;

            // Go with image width by default & only compute target width if max-width is set
            var width = this.img.width;
            if (img.css('max-height') !== 'none') {
              // Calculate target width by taking into account source dimensions
              width = Math.ceil(img.attr('width') / img.attr('height') * this.img.height);
            }

            var parent = img.parent();
            parent.imagesLoaded( function() {
              $(this.images).each(function() {
                parent.removeClass('loading');
              });
            });

            // Replace placeholder with appropriately sized actual image
            this.img.src = '/image?file=' + this.img.dataset.clientHintsSrc + '&dpr=' + dpr + '&width=' + width;

          }

        });

      });

    }
  };

}(jQuery, Drupal));
