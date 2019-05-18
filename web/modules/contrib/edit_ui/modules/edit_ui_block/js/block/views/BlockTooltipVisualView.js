/**
 * @file
 * Extends the Backbone view for the edit_ui block's tooltip element.
 */

(function (Drupal) {
  "use strict";

  /**
   * {@inheritdoc}
   */
  var parentInitialize = Drupal.editUi.block.BlockTooltipVisualView.prototype.initialize;
  Drupal.editUi.block.BlockTooltipVisualView.prototype.initialize = function (options) {
    parentInitialize.bind(this)(options);

    // Add listeners.
    this.listenTo(this.model, 'change:isDragging', this.hideTooltip);
  };

}(Drupal));
