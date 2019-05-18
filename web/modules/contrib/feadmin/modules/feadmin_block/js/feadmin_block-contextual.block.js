/**
 * @file
 * Drupal behavior for the feadmin_block blocks.
 * Sponsored by: www.freelance-drupal.com
 */

(function (Drupal, $) {
  'use strict';

  /**
   * feadmin_block block Backbone objects.
   */
  Drupal.feaAdmin.block.views.contextualVisualView = [];

  /**
   * Add feadmin_block custom behavior on blocks.
   *
   * @param {Drupal.editUi.block.BlockModel} block
   *   The feadmin block model.
   *
   * @listens event:add
   */
  Drupal.feaAdmin.block.collections.blockCollection.on('add', function (block) {
    var $block = $('#' + block.get('html_id'));
    if ($block.length > 0) {
      Drupal.feaAdmin.block.views.contextualVisualView = new Drupal.feaAdmin.block.ContextualVisualView({
        model: block,
        el: $block
      });
    }
  });

})(Drupal, jQuery);
