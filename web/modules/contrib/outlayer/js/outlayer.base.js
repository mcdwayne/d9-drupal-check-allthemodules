/**
 * @file
 * Provides GridStack Outlayer base shared utility methods.
 */

(function (Drupal, _db) {

  'use strict';

  Drupal.outLayer = Drupal.outLayer || {};

  /**
   * Outlayer utility functions.
   *
   * @namespace
   */
  Drupal.outLayer.base = {

    /**
     * Updates grid boxes with the given aspect ratios to make them responsive.
     *
     * Basically avoids hard-coding widths and heights thanks to padding hack.
     * This is one good reason to avoid using Imagesloaded plugin since we
     * don't have to wait for any image to finish loading to have a grid layout.
     * Consider this as a frame for a picture image.
     *
     * @param {HTMLElement} items
     *   The child HTML elements.
     */
    updateRatio: function (items) {
      _db.forEach(items, function (item) {
        var width = item.hasAttribute('data-ol-width') ? parseInt(item.getAttribute('data-ol-width')) : -1;
        var height = item.hasAttribute('data-ol-height') ? parseInt(item.getAttribute('data-ol-height')) : -1;
        var pad = Math.round(((height / width) * 100), 2);
        var $media = item.querySelectorAll('.media--ratio');

        // Supports multiple media such as an embedded Slick carousel.
        if ($media !== null && height > 0 && width > 0) {
          _db.forEach($media, function (medium) {
            medium.style.paddingBottom = pad + '%';
          });
        }
      });
    }

  };

}(Drupal, dBlazy));
