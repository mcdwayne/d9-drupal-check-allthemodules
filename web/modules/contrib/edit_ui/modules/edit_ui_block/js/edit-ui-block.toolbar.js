/**
 * @file
 *
 * Drupal behavior for the edit_ui toolbar.
 */

(function (Drupal, $) {
  "use strict";

  /**
   * Drupal edit_ui toolbar behavior.
   */
  Drupal.behaviors.editUiBlockToolbar = {
    attach: function (context, settings) {
      if (!this.isInitialized) {
        this.isInitialized = true;

        // Init toolbar model.
        var model = Drupal.editUi.toolbar.models.toolbarModel = new Drupal.editUi.toolbar.ToolbarModel();

        // Init toolbar view.
        var toolbar = document.getElementById('edit-ui-toolbar');
        if (toolbar) {
          Drupal.editUi.toolbar.views.toolbarVisualView = new Drupal.editUi.toolbar.ToolbarVisualView({
            el: toolbar,
            model: model
          });
        }

        // Init menu view.
        Drupal.editUi.toolbar.views.menuVisualView = new Drupal.editUi.toolbar.MenuVisualView({
          el: document.getElementsByClassName('js-edit-ui__menu'),
          model: model
        });

        // Init body view.
        Drupal.editUi.toolbar.views.bodyVisualView = new Drupal.editUi.toolbar.BodyVisualView({
          model: model
        });
      }
    }
  };

  /**
   * edit_ui toolbar Backbone objects.
   */
  Drupal.editUi.toolbar = {
    // A hash of View instances.
    views: {},
    // A hash of Model instances.
    models: {}
  };
})(Drupal, jQuery);
