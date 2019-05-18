/**
 * @file
 * Provides Outlayer Masonry loader.
 */

(function (Drupal, drupalSettings, Packery, _db) {

  'use strict';

  Drupal.outLayer = Drupal.outLayer || {};

  /**
   * Outlayer utility functions.
   *
   * @namespace
   */
  Drupal.outLayer.packery = {
    $instance: null,
    $el: null
  };

  /**
   * Outlayer utility functions.
   *
   * @param {HTMLElement} grid
   *   The Outlayer HTML element.
   */
  function doOutLayerPackery(grid) {
    var me = Drupal.outLayer.packery;
    var opts = grid.getAttribute('data-outlayer-packery') ? _db.parse(grid.getAttribute('data-outlayer-packery')) : {};
    var $items = grid.querySelectorAll('.gridstack__box');

    // Define aspect ratio before laying out.
    Drupal.outLayer.base.updateRatio($items);

    // Pass data to Drupal.outLayer.packery for easy reference.
    me.$el = grid;
    me.$instance = new Packery(grid, opts);

    grid.classList.add('outlayer--packery--on');
  }

  /**
   * Attaches Outlayer behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.outLayerPackery = {
    attach: function (context) {
      var galleries = context.querySelectorAll('.outlayer--packery:not(.outlayer--packery--on)');
      _db.once(_db.forEach(galleries, doOutLayerPackery, context));
    }
  };

}(Drupal, drupalSettings, Packery, dBlazy));
