/**
 * @file
 * A Backbone Model for the feadmin toolbar.
 *
 * Sponsored by: www.freelance-drupal.com
 */

(function (Drupal, Backbone, $) {
  'use strict';

  Drupal.feaAdmin = Drupal.feaAdmin || {};

  /**
   * Backbone model for the feadmin toolbar.
   */
  Drupal.feaAdmin.toolbar.ToolbarModel = Backbone.Model.extend({
    defaults: {
      // None open completely lets the toolbar disappear
      // (case when toolbar module is enabled).
      isOpen: false,
      // Collapsed lets the toolbar on the right side of screen.
      isCollapsed: false,
      topOffset: 0,
      activeTool: null
    },

    /**
     * Custom data.
     */
    cookieName: 'feadmin',

    /**
     * {@inheritdoc}
     */
    initialize: function () {
      this.fetch();
      if (Drupal.toolbar) {
        this.listenTo(Drupal.toolbar.models.toolbarModel, 'change:offsets', this.setTopOffset);
      }
    },

    /**
     * {@inheritdoc}
     */
    sync: function (method, model, options) {
      var resp;
      var cookie;

      switch (method) {
        case 'read':
          cookie = $.cookie(this.cookieName);
          if (cookie) {
            resp = JSON.parse(cookie);
            model.set(resp);
          }
          break;

        case 'create':
        case 'update':
          resp = model;
          $.cookie(this.cookieName, JSON.stringify(resp), {path: '/'});
          break;

        case 'delete':
          resp = model;
          $.removeCookie(this.cookieName);
          break;
      }

      options.success(resp);
    },

    /**
     * {@inheritdoc}
     */
    toJSON: function () {
      return {isOpen: this.get('isOpen')};
    },

    /**
     * Toggle toolbar.
     */
    toggle: function () {
      this.save({isOpen: !this.get('isOpen')});
    },

    /**
     * Collapse toolbar.
     */
    collapse: function () {
      this.save({isCollapsed: !this.get('isCollapsed')});
    },

    /**
     * Toggle toolbar.
     *
     * @param bool
     *   TRUE/FALSE where to activate the tool.
     */
    activateTool: function (bool) {
      this.save({activeTool: bool});
    },

    /**
     * Set top offset.
     */
    setTopOffset: function () {
      var topOffset = Drupal.toolbar.models.toolbarModel.get('offsets').top;
      this.save({topOffset: topOffset});
    }
  });

}(Drupal, Backbone, jQuery));
