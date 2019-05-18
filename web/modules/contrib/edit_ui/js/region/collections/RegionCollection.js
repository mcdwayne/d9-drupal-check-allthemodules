/**
 * @file
 * A Backbone Collection for the edit_ui region.
 */

(function (Drupal, Backbone, drupalSettings) {

  "use strict";

  /**
   * Backbone collection for the edit_ui region.
   */
  Drupal.editUi.region.RegionCollection = Backbone.Collection.extend({
    /**
     * {@inheritdoc}
     */
    model: Drupal.editUi.region.RegionModel,

    /**
     * Initiliaze regions data.
     */
    init: function () {
      this.models.forEach(function (region) {
        region.trigger('init');
      });
    },

    /**
     * Initiliaze regions when dragging.
     *
     * @param Drupal.editUi.block.BlockModel block
     *   The dragged block.
     */
    startDrag: function (block) {
      this.models.forEach(function (region) {
        region.trigger('startDrag', block);
      });
    },

    /**
     * Reset regions for dragging.
     *
     * @param Drupal.editUi.region.RegionModel region
     *   The region to exclude.
     */
    resetDrag: function (region) {
      this.reject({region: region.get('region')})
        .forEach(function (region) {
          region.trigger('resetDrag');
        });
    },

    /**
     * Find hovered region.
     *
     * @param Drupal.editUi.block.BlockModel block
     *   The dragged block.
     * @param Number x
     *   Mouse left position.
     * @param Number y
     *   Mouse top position.
     */
    drag: function (block, x, y) {
      var isInsideTrash;
      var region = this.getActiveRegion();

      // Trash is first priority.
      if (Drupal.editUi.region.models.trashModel) {
        isInsideTrash = Drupal.editUi.region.views.trashVisualView.isInside({x: x, y: y});
        if (isInsideTrash) {
          if (region) {
            region.deactivate();
          }
          region = Drupal.editUi.region.models.trashModel;
        }
      }

      if (region) {
        // Trigger drag event only to active region.
        region.trigger('drag', block, {x: x, y: y});
      }
      else {
        // Trigger drag event to all regions.
        this.models.forEach(function (region) {
          region.trigger('drag', block, {x: x, y: y});
        });
      }
    },

    /**
     * Drop block.
     *
     * @param Drupal.editUi.block.BlockModel block
     *   The dropped block.
     * @param Number x
     *   Mouse left position.
     * @param Number y
     *   Mouse top position.
     */
    drop: function (block, x, y) {
      var region = this.getActiveRegion();

      if (region) {
        // Drop block in region.
        region.trigger('drop', block);
      }
      else if (!block.isNew() && !drupalSettings.edit_ui_block.revert_on_spill) {
        // Drop block in the last place.
        this.getRegion(block.get('region')).trigger('drop', block);
      }

      this.models.forEach(function (region) {
        region.trigger('stopDrag');
      });
    },

    /**
     * Get region from given input.
     *
     * @param String region
     *   Region name.
     * @return Drupal.editUi.region.RegionModel
     */
    getRegion: function (region) {
      return this.findWhere({region: region});
    },

    /**
     * Get the active region.
     *
     * @return Drupal.editUi.region.RegionModel
     */
    getActiveRegion: function () {
      return this.findWhere({isActive: true});
    }
  });

  // Init collection.
  Drupal.editUi.region.collections.regionCollection = new Drupal.editUi.region.RegionCollection();

}(Drupal, Backbone, drupalSettings));
