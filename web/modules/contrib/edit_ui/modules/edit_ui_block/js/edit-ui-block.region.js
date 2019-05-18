/**
 * @file
 *
 * Drupal behavior for the edit_ui regions.
 */

(function (Drupal, $) {
  "use strict";

  /**
   * Drupal edit_ui_block region behavior.
   */
  Drupal.behaviors.editUiBlockRegion = {
    attach: function (context, settings) {
      if (!this.isInitialized) {
        var trash;
        var model;

        trash = document.getElementsByClassName('js-edit-ui__trash');
        if (trash) {
          // Init trash model.
          model = Drupal.editUi.region.models.trashModel = new Drupal.editUi.region.RegionModel({
            region: 'edit-ui__trash'
          });

          // Add model to collection.
          Drupal.editUi.region.collections.regionCollection.add(model);

          // Init trash view.
          Drupal.editUi.region.views.trashVisualView = new Drupal.editUi.region.TrashVisualView({
            el: trash,
            model: model
          });
        }

        this.isInitialized = true;
      }
    }
  };

  /**
   * Initialize region views when region is added to collection.
   *
   * @param Drupal.editUi.region.RegionModel model
   *   Region model instance.
   *
   * @listens event:add
   */
  Drupal.editUi.region.collections.regionCollection.on('add', function (model) {
    var view;
    var region = model.get('region');
    var $el = $('[data-edit-ui-region=' + region + ']');

    // Init region view.
    if ($el.length > 0) {
      view = new Drupal.editUi.region.RegionVisualView({
        model: model,
        el: $el
      });
      Drupal.editUi.region.views.regionVisualView.push(view);
    }
  });

  /**
   * edit_ui region Backbone objects.
   */
  Drupal.editUi.region.views = {
    regionVisualView: []
  };

})(Drupal, jQuery);
