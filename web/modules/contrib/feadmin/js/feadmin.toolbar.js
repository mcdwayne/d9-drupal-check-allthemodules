/**
 * @file
 * Drupal behavior for the feadmin toolbar.
 *
 * Sponsored by: www.freelance-drupal.com
 */

(function (Drupal) {
  'use strict';

  Drupal.feaAdmin = Drupal.feaAdmin || {};

  /**
   * Drupal feadmin toolbar behavior.
   */
  Drupal.behaviors.feaAdminToolbar = {
    attach: function () {
      if (!this.isInitialized) {
        this.isInitialized = true;

        // Init toolbar model.
        var toolbarModel = Drupal.feaAdmin.toolbar.models.toolbarModel = new Drupal.feaAdmin.toolbar.ToolbarModel();

        // Init menu view.
        var toolbar_toogler = document.getElementsByClassName('js-feadmin-toogle');
        var embedded_toogler = document.getElementsByClassName('toolbar-tray-toogler');
        if (toolbar_toogler.length) {
          Drupal.feaAdmin.toolbar.views.menuVisualView = new Drupal.feaAdmin.toolbar.MenuVisualView({
            el: toolbar_toogler,
            model: toolbarModel
          });
        }
        else if (embedded_toogler.length) {
          Drupal.feaAdmin.toolbar.views.tooglerVisualView = new Drupal.feaAdmin.toolbar.TooglerVisualView({
            el: embedded_toogler,
            model: toolbarModel
          });
        }

        // Init toolbar view.
        var toolbar = document.getElementById('feadmin-toolbar');
        if (toolbar) {
          Drupal.feaAdmin.toolbar.views.toolbarVisualView = new Drupal.feaAdmin.toolbar.ToolbarVisualView({
            el: toolbar,
            model: toolbarModel
          });
        }

        // Init body view.
        Drupal.feaAdmin.toolbar.views.bodyVisualView = new Drupal.feaAdmin.toolbar.BodyVisualView({
          model: toolbarModel
        });
      }
    }
  };

  /**
   * FeaAdmin toolbar Backbone objects.
   */
  Drupal.feaAdmin.toolbar = {
    // A hash of View instances.
    views: {},
    // A hash of Model instances.
    models: {}
  };

})(Drupal);
