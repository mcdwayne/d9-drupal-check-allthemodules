/**
 * @file
 * Drupal behavior for the edit_ui blocks.
 */

(function (Drupal, $) {
  "use strict";

  /**
   * Drupal edit_ui block behavior.
   */
  Drupal.behaviors.editUiBlock = {
    attach: function (context, settings) {
      if (!this.isInitialized) {
        this.isInitialized = true;

        // Trigger custom event.
        $(document).trigger('editUiBlockInitBefore');

        // Add listeners.
        Drupal.editUi.block.collections.blockCollection.on('add', this.blockAdded.bind(this));

        // Initialize collection.
        $(window).one('load', function () {
          Drupal.editUi.block.collections.blockCollection.fetch();
        });
      }
    },

    /**
     * Block added callback (called when block has been added to collection).
     *
     * @param Drupal.editUi.block.BlockModel block
     *   Block model instance.
     */
    blockAdded: function (block) {
      if (!Drupal.editUi.region) {
        return;
      }

      var region = Drupal.editUi.region.collections.regionCollection.getRegion(block.get('region'));

      // Region is empty when a block is created from the toolbar.
      if (!region) {
        return;
      }

      if ($('#' + block.get('html_id')).length === 0) {
        if (drupalSettings.edit_ui_block.display_hidden_blocks) {
          // Wait other blocks initializes and create a placeholder block.
          window.setTimeout($.proxy(this.initBlock, this, block), 0);
        }
      }
      else {
        this.initViews(block, $('#' + block.get('html_id')));
      }
    },

    /**
     * Initializes a block and add it to the region.
     *
     * @param Drupal.editUi.block.BlockModel block
     *   Block model instance.
     * @param jQuery $el
     *   Block DOM jQuery object.
     */
    initBlock: function (block, $el) {
      if (!$el || $el.length === 0) {
        // Create a placeholder block.
        $el = $(Drupal.theme('editUiBlockPlaceholderBlock', block.attributes));
      }

      // Add block into region.
      Drupal.editUi.region.collections.regionCollection
        .getRegion(block.get('region'))
        .trigger('addBlock', block, $el);

      // Other initilalizations.
      Drupal.attachBehaviors($el.get(0));
      this.initViews(block, $el);
    },

    /**
     * Init views for block.
     *
     * @param Drupal.editUi.block.BlockModel block
     *   Block model instance.
     * @param jQuery $block
     *   Block DOM jQuery object.
     */
    initViews: function (block, $block) {
      var blockVisualView;
      var blockTooltipVisualView;
      var $el;

      // Add HTML wrapper.
      $el = $(Drupal.theme('editUiBlockWrapperBlock', block.attributes));
      $block.after($el);
      $el.wrapInner($block);

      // Init the block view.
      blockVisualView = new Drupal.editUi.block.BlockVisualView({
        el: $el,
        model: block
      });
      Drupal.editUi.block.views.blockVisualViews.push(blockVisualView);

      // Init the block's tooltip.
      blockTooltipVisualView = new Drupal.editUi.block.BlockTooltipVisualView({
        el: $el,
        model: block
      });
      Drupal.editUi.block.views.blockTooltipVisualViews.push(blockTooltipVisualView);
    }
  };

  /**
   * edit_ui block Backbone objects.
   */
  Drupal.editUi = Drupal.editUi || {};
  Drupal.editUi.block = {
    // A hash of View instances.
    views: {},
    // A hash of Collection instances.
    collections: {},
    // A hash of Model instances.
    models: {
      newBlockModel: null
    },
    // A hash of jQuery elements.
    elements: {}
  };

})(Drupal, jQuery);
