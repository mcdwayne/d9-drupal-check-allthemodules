/**
 * @file
 *
 * Drupal behavior for the edit_ui regions.
 */

(function (Drupal, $) {
  "use strict";

  /**
   * Drupal edit_ui region behavior.
   */
  Drupal.behaviors.editUiRegion = {
    attach: function (context, settings) {
      if (!this.isInitialized) {
        this.isInitialized = true;

        // Trigger custom event.
        $(document).trigger('editUiRegionInitBefore');

        // Init data.
        $('.js-edit-ui__region').each(function (index, el) {
          var model;
          var region = $(el).data('edit-ui-region');

          // Init region model.
          model = new Drupal.editUi.region.RegionModel({
            region: region
          });
          Drupal.editUi.region.models.regionModel.push(model);

          // Add model to collection.
          Drupal.editUi.region.collections.regionCollection.add(model);
        });
      }
    }
  };

  /**
   * edit_ui region Backbone objects.
   */
  Drupal.editUi = Drupal.editUi || {};
  Drupal.editUi.region = {
    // A hash of Model instances.
    models: {
      regionModel: []
    },
    // A hash of Collection instances.
    collections: {}
  };

})(Drupal, jQuery);
