/**
 * @file
 * A Backbone Model for the edit_ui region.
 */

(function (Drupal, Backbone) {

  "use strict";

  /**
   * Backbone model for the edit_ui region.
   */
  Drupal.editUi.region.RegionModel = Backbone.Model.extend({
    defaults: {
      region: '',
      block: null,
      isActive: false,
      isSelected: false,
      offset: {
        top: 0,
        left: 0
      },
      dimensions: {
        width: 0,
        height: 0
      }
    },

    /**
     * {@inheritdoc}
     */
    initialize: function (options) {
      this.set({region: options.region + ''});
    },

    /**
     * Set region active state.
     */
    activate: function () {
      this.set({isActive: true});
    },

    /**
     * Set region not active state.
     */
    deactivate: function () {
      this.set({isActive: false});
    },

    /**
     * Set region select state.
     */
    select: function () {
      this.set({isSelected: true});
    },

    /**
     * Set region select state.
     */
    unselect: function () {
      this.set({isSelected: false});
    },

    /**
     * Set region edit_ui block.
     *
     * @param jQuery $block
     *   Edit_ui block.
     */
    setBlock: function ($block) {
      this.set({block: $block});
    },

    /**
     * Set region offset.
     *
     * @param Object offset
     *   Region offset.
     */
    setOffset: function (offset) {
      this.set({offset: offset});
    },

    /**
     * Set region dimensions.
     *
     * @param Object dimensions
     *   Region dimensions.
     */
    setDimensions: function (dimensions) {
      this.set({dimensions: dimensions});
    },

    /**
     * Get the model representing the element that are just before
     * the dragged/dropped element using position as compare value.
     *
     * @param Number value
     *   The top position.
     * @return mixed
     *   The model representing the element.
     */
    getBeforeModelByPosition: function (value) {
      var i;
      var beforeModel;
      var compareValue;
      var prospectiveModels = {};
      var blocks = Drupal.editUi.block.collections.blockCollection.getRegionBlocks(this.get('region'));

      // Check for prospective before Model.
      for (i = 0; i < blocks.length; i++) {
        compareValue = blocks[i].get('offset').top + blocks[i].get('dimensions').height / 2;
        if (blocks[i].get('isVisible') && value > compareValue) {
          prospectiveModels[compareValue] = blocks[i];
        }
      }

      // Get the closest one.
      if (Object.keys(prospectiveModels).length) {
        beforeModel = prospectiveModels[Math.max.apply(null, Object.keys(prospectiveModels))];
      }

      // If not found the before model is equal to the region.
      if (!beforeModel) {
        beforeModel = this;
      }

      return beforeModel;
    },

    /**
     * Get the model representing the element that is just before
     * the dragged/dropped element using weight as compare value.
     *
     * @param Number value
     *   The block weight.
     * @return mixed
     *   The model representing the element.
     */
    getBeforeModelByWeight: function (value) {
      var compareValue;
      var beforeModel;
      var blocks;
      var i;

      // Force block sort.
      Drupal.editUi.block.collections.blockCollection.sort();
      blocks = Drupal.editUi.block.collections.blockCollection.getRegionBlocks(this.get('region'));
      i = blocks.length - 1;

      // Fetch before Model.
      while (blocks[i] && !beforeModel) {
        compareValue = blocks[i].get('weight');
        if (blocks[i].get('isVisible') && value > compareValue) {
          beforeModel = blocks[i];
        }
        else {
          i--;
        }
      }

      // If not found the before model is equal to the region.
      if (!beforeModel) {
        beforeModel = this;
      }

      return beforeModel;
    }
  });

}(Drupal, Backbone));
