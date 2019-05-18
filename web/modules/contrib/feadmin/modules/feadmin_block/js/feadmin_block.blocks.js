/**
 * @file
 * Front-end Administration handling of blocks.
 *
 * Sponsored by: www.freelance-drupal.com
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Start the block handling behavior:
   *  - move block around, from one region to another.
   */
  Drupal.behaviors.feadmin_block = {
    attach: function () {

      if (!this.isInitialized) {
        this.isInitialized = true;

        // Init block model.
        var blockModel = Drupal.feaAdmin.block.models.blockModel = new Drupal.feaAdmin.block.BlockModel();

        // Init block view
        var blockView = $('[data-block]');
        Drupal.feaAdmin.block.views.blockView = [];
        Drupal.feaAdmin.block.views.blockDeleteButtonVisualView = [];
        blockView.each(function (index, elem) {
          Drupal.feaAdmin.block.views.blockView[index] = new Drupal.feaAdmin.block.BlockView({
            model: blockModel,
            el: elem
          });

          // Init contextual view.
          var deleter = $('.feadmin-links-delete a', elem);
          if (deleter.length) {
            Drupal.feaAdmin.block.views.blockDeleteButtonVisualView[index] = new Drupal.feaAdmin.block.BlockDeleteButtonVisualView({
              el: deleter,
              model: blockModel
            });
          }

        });

        // Init body view.
        Drupal.feaAdmin.block.views.bodyVisualView = new Drupal.feaAdmin.block.BodyVisualView({
          model: Drupal.feaAdmin.toolbar.models.toolbarModel
        });
      }
    }
  };

  /**
   * feaAdmin block Backbone objects.
   */
  Drupal.feaAdmin.block = {
    // A hash of View instances.
    views: {},
    // A hash of Model instances.
    models: {}
  };

})(jQuery, Drupal);
