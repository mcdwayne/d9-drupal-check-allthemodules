/**
 * @file
 * A Backbone view for the edit_ui region element.
 */

(function (Drupal, drupalSettings) {
  "use strict";

  /**
   * Custom data.
   */
  Drupal.editUi.region.RegionVisualView.prototype.activeClass = 'is-edit-ui-region-active';
  Drupal.editUi.region.RegionVisualView.prototype.selectClass = 'is-edit-ui-region-selected';

  /**
   * {@inheritdoc}
   */
  var parentInitialize = Drupal.editUi.region.RegionVisualView.prototype.initialize;
  Drupal.editUi.region.RegionVisualView.prototype.initialize = function (options) {
    parentInitialize.apply(this, options);

    // Add listeners.
    this.listenTo(this.model, 'change:isActive', this.toggleActive);
    this.listenTo(this.model, 'change:isSelected', this.toggleSelected);
    this.listenTo(this.model, 'startDrag', this.startDrag);
    this.listenTo(this.model, 'resetDrag', this.resetDrag);
    this.listenTo(this.model, 'drag', this.drag);
    this.listenTo(this.model, 'drop', this.drop);

    // Initialize default.
    this.revertOnSpill = drupalSettings.edit_ui_block.revert_on_spill;
  };

  /**
   * Toggle class depending on model.
   */
  Drupal.editUi.region.RegionVisualView.prototype.toggleActive = function () {
    this.$el.toggleClass(this.activeClass, this.model.get('isActive'));
  };

  /**
   * Toggle class depending on model.
   */
  Drupal.editUi.region.RegionVisualView.prototype.toggleSelected = function () {
    this.$el.toggleClass(this.selectClass, this.model.get('isSelected'));
  };

  /**
   * Initiliaze region when dragging.
   *
   * @param Drupal.editUi.block.BlockModel block
   *   The dragged block.
   */
  Drupal.editUi.region.RegionVisualView.prototype.startDrag = function (block) {
    this.beforeModel = null;
    if (this.model.get('region') === block.get('region')) {
      this.startBeforeModel = this.model.getBeforeModelByWeight(block.get('weight'));
    }
    else {
      this.startBeforeModel = null;
    }
  };

  /**
   * Reset region for dragging.
   */
  Drupal.editUi.region.RegionVisualView.prototype.resetDrag = function () {
    this.beforeModel = null;
  };

  /**
   * Check if block is inside the region.
   *
   * @param Object args
   *   The drag position.
   * @return boolean
   *   Inside or not.
   */
  Drupal.editUi.region.RegionVisualView.prototype.isInside = function (args) {
    var regionOffset = this.model.get('offset');
    var regionDimensions = this.model.get('dimensions');
    return args.x > regionOffset.left &&
           args.x < regionOffset.left + regionDimensions.width &&
           args.y > regionOffset.top &&
           args.y < regionOffset.top + regionDimensions.height;
  };

  /**
   * Drag event callback.
   *
   * @param Drupal.editUi.block.BlockModel block
   *   The block model of the dragged element.
   * @param Object args
   *   The drag position.
   */
  Drupal.editUi.region.RegionVisualView.prototype.drag = function (block, args) {
    var beforeModel;
    var region = block.get('region');

    if (!this.isInside(args)) {
      if (this.model.get('isActive')) {
        // Leave the region.
        this.model.deactivate();

        if (!block.isNew() && region && this.revertOnSpill) {
          this.beforeModel = null;
          region = Drupal.editUi.region.collections.regionCollection.getRegion(block.get('startRegion'));
          region.trigger('addBlock', block, block.get('block'));
        }
        else if (block.isNew()) {
          this.beforeModel = null;
          Drupal.editUi.block.models.draggingBlockModel.trigger('update');
        }

        // Recalculate dimensions.
        Drupal.editUi.utils.calculateDimensions();
      }
    }
    else {
      if (!this.model.get('isActive')) {
        // Enter the region.
        this.model.activate();

        // Reset drag for all other regions (but this region).
        Drupal.editUi.region.collections.regionCollection.resetDrag(this.model);
      }

      // Manage block order inside the region.
      beforeModel = this.model.getBeforeModelByPosition(args.y);

      if (beforeModel === this.beforeModel || beforeModel === block) {
        // No changes => skip.
        return;
      }
      this.beforeModel = beforeModel;

      if (region) {
        // Existing block.
        this.model.trigger('addBlock', block, block.get('block'));
      }
      else {
        // New block.
        if (beforeModel !== block) {
          Drupal.editUi.block.models.draggingBlockModel.trigger('update', beforeModel.get('block'));
        }
        else {
          Drupal.editUi.block.models.draggingBlockModel.trigger('update');
        }
      }

      // Recalculate dimensions.
      Drupal.editUi.utils.calculateDimensions();
    }
  };

  /**
   * Drop event callback.
   *
   * @param Drupal.editUi.block.BlockModel block
   *   The block model of the dropped element.
   */
  Drupal.editUi.region.RegionVisualView.prototype.drop = function (block) {
    var weight;
    var blocks;
    var region = this.model.get('region');

    if (!block || !this.beforeModel || this.beforeModel === this.startBeforeModel) {
      // In that case we haven't do anything.
      return;
    }

    blocks = Drupal.editUi.block.collections.blockCollection
      .getRegionBlocks(region)
      .filter(function (model) {
        return model.cid !== block.cid;
      });

    // Calculate and update weights.
    if (this.beforeModel instanceof Drupal.editUi.block.BlockModel) {
      weight = this.beforeModel.get('weight') + 1;
      block.drop(this.model.get('region'), weight);

      // We must update all following block weight.
      blocks
        .slice(blocks.indexOf(this.beforeModel) + 1)
        .forEach(function (model) {
          weight++;
          model.setWeight(weight);
        });
    }
    else if (blocks[0]) {
      block.drop(this.model.get('region'), blocks[0].get('weight') - 1);
    }
    else {
      block.drop(this.model.get('region'), 0);
    }

    // Trigger addBlock event.
    this.model.trigger('addBlock', block, block.get('block'));
    this.beforeModel = null;
  };

}(Drupal, drupalSettings));
