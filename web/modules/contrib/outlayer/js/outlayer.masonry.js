/**
 * @file
 * Provides Outlayer Masonry loader.
 */

(function (Drupal, drupalSettings, Masonry, _db) {

  'use strict';

  Drupal.outLayer = Drupal.outLayer || {};

  /**
   * Outlayer utility functions.
   *
   * @namespace
   */
  Drupal.outLayer.masonry = {
    $instance: null,
    $el: null
  };

  /**
   * Outlayer utility functions.
   *
   * @param {HTMLElement} grid
   *   The Outlayer HTML element.
   */
  function doOutLayerMasonry(grid) {
    var me = Drupal.outLayer.masonry;
    var opts = grid.getAttribute('data-outlayer-masonry') ? _db.parse(grid.getAttribute('data-outlayer-masonry')) : {};
    var $items = grid.querySelectorAll('.gridstack__box');

    // Define aspect ratio before laying out.
    Drupal.outLayer.base.updateRatio($items);

    // Pass data to Drupal.outLayer.masonry for easy reference.
    me.$el = grid;
    me.$instance = new Masonry(grid, opts);

    grid.classList.add('outlayer--masonry--on');
  }

  /**
   * Attaches Outlayer behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.outLayerMasonry = {
    attach: function (context) {
      var galleries = context.querySelectorAll('.outlayer--masonry:not(.outlayer--masonry--on)');
      _db.once(_db.forEach(galleries, doOutLayerMasonry, context));
    }
  };

}(Drupal, drupalSettings, Masonry, dBlazy));
