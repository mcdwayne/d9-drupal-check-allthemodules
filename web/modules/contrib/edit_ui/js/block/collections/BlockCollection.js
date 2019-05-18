/**
 * @file
 * A Backbone Collection for the edit_ui block.
 */

(function (Drupal, Backbone, drupalSettings) {

  "use strict";

  /**
   * Backbone collection for the edit_ui block.
   */
  Drupal.editUi.block.BlockCollection = Backbone.Collection.extend({
    /**
     * {@inheritdoc}
     */
    model: Drupal.editUi.block.BlockModel,

    /**
     * {@inheritdoc}
     */
    url: Drupal.url('edit-ui/block'),

    /**
     * {@inheritdoc}
     */
    comparator: function (a, b) {
      if (a.get('weight') === b.get('weight')) {
        return a.get('offset').top > b.get('offset').top ? 1 : -1;
      }
      else {
        return a.get('weight') > b.get('weight') ? 1 : -1;
      }
    },

    /**
     * {@inheritdoc}
     */
    initialize: function (options) {
      this.on('change:weight', this.sort);
    },

    /**
     * Return blocks bellonging to given region.
     *
     * @param String region
     *   Region name.
     * @return Array
     */
    getRegionBlocks: function (region) {
      return this.where({region: region});
    },

    /**
     * Save all blocks in all regions.
     */
    save: function () {
      if (drupalSettings.edit_ui_block.save_button) {
        this.where({unsaved: true})
          .forEach(function (block) {
            block.save();
          });
      }
    },

    /**
     * Check if there is at least an unsaved block.
     *
     * @return boolean
     *   All block are saved or not.
     */
    hasUnsavedChanges: function () {
      var unsaved = false;

      this.forEach(function (block) {
        if (block.get('unsaved')) {
          unsaved = true;
        }
      });

      return unsaved;
    }
  });

  // Init collection.
  Drupal.editUi.block.collections.blockCollection = new Drupal.editUi.block.BlockCollection();

}(Drupal, Backbone, drupalSettings));
