/**
 * @file
 * A Backbone view for the edit_ui region element.
 */

(function (Drupal, Backbone) {
  "use strict";

  /**
   * Backbone view for the edit_ui region.
   */
  Drupal.editUi.region.RegionVisualView = Backbone.View.extend({
    /**
     * {@inheritdoc}
     */
    initialize: function (options) {
      // Add listeners.
      this.listenTo(this.model, 'addBlock', this.addBlock);
      this.listenTo(this.model, 'calculateDimensions', this.calculateDimensions);

      // Get DOM elements.
      this.$block = this.$el.children('.js-edit-ui__region-block');

      // Initialize default.
      this.model.setBlock(this.$block);
    },

    /**
     * Add block in the region.
     *
     * @param Drupal.editUi.block.BlockModel addedBlock
     *   The model of the block.
     * @param jQuery $block
     *   The block element.
     */
    addBlock: function (addedBlock, $block) {
      var beforeModel;

      if (!this.beforeModel) {
        beforeModel = this.model.getBeforeModelByWeight(addedBlock.get('weight'));
      }
      else {
        beforeModel = this.beforeModel;
      }

      // Insert block.
      addedBlock.set('region', this.model.get('region'));
      beforeModel.get('block').after($block);
    },

    /**
     * Calculate the region dimensions.
     */
    calculateDimensions: function () {
      var offset = this.$el.offset();
      var paddingTop = this.$el.css('paddingTop');
      var paddingLeft = this.$el.css('paddingLeft');

      // Remove px unit.
      paddingTop = Number(paddingTop.substr(0, paddingTop.indexOf('px')));
      paddingLeft = Number(paddingLeft.substr(0, paddingLeft.indexOf('px')));

      // Save dimensions.
      this.model.setOffset({
        top: offset.top + paddingTop,
        left: offset.left + paddingLeft
      });
      this.model.setDimensions({
        width: this.$el.width(),
        height: this.$el.height()
      });
    }
  });

}(Drupal, Backbone));
