/**
 * @file
 * Drupal behavior for the edit_ui blocks.
 */

(function (Drupal, $) {
  "use strict";

  /**
   * Initialize views.
   */
  $(document).one('editUiBlockInitBefore', function () {
    // Init dragging block model and views.
    var model = Drupal.editUi.block.models.draggingBlockModel = new Drupal.editUi.block.DraggingBlockModel();
    Drupal.editUi.block.views.draggingBlockVisualView = new Drupal.editUi.block.DraggingBlockVisualView({
      model: model,
      el: $('#edit-ui__dragging')
    });
    Drupal.editUi.block.views.ghostBlockVisualView = new Drupal.editUi.block.GhostBlockVisualView({
      model: model,
      el: $(Drupal.theme('editUiBlockGhostBlock'))
    });

    // Init block views.
    Drupal.editUi.block.views.bodyVisualView = new Drupal.editUi.block.BodyVisualView({
      collection: Drupal.editUi.block.collections.blockCollection
    });

    // Init tooltip fence.
    Drupal.editUi.block.elements.editUiTooltipFence = $(Drupal.theme('editUiBlockTooltipFence'));
    Drupal.editUi.block.elements.editUiTooltipFence.appendTo('body');
  });

  /**
   * edit_ui block Backbone objects.
   */
  Drupal.editUi.block.views.blockVisualViews = [];
  Drupal.editUi.block.views.blockTooltipVisualViews = [];

})(Drupal, jQuery);
