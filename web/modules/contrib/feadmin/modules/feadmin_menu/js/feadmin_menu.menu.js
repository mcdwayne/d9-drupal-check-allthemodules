/**
 * @file
 * Front-end Administration handling of menus.
 *
 * Sponsored by: www.freelance-drupal.com
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Start the menu handling behavior by instanciating Backbone objects.
   */
  Drupal.behaviors.feadmin_menu = {
    attach: function () {
      if (!this.isInitialized) {
        this.isInitialized = true;

        // Init body view.
        Drupal.feaAdmin.menu.views.bodyVisualView = new Drupal.feaAdmin.menu.BodyVisualView({
          model: Drupal.feaAdmin.toolbar.models.toolbarModel
        });
      }
    }
  };

  /**
   * FeaAdmin menu Backbone objects.
   */
  Drupal.feaAdmin.menu = {
    // A hash of View instances.
    views: {},
    // A hash of Model instances.
    models: {}
  };

})(jQuery, Drupal);
