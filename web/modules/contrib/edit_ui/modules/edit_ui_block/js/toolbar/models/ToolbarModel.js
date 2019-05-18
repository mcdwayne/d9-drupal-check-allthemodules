/**
 * @file
 * A Backbone Model for the edit_ui toolbar.
 */

(function (Drupal, Backbone, $) {

  "use strict";

  /**
   * Backbone model for the edit_ui toolbar.
   */
  Drupal.editUi.toolbar.ToolbarModel = Backbone.Model.extend({
    defaults: {
      isOpen: false,
      topOffset: 0
    },

    /**
     * Custom data.
     */
    cookieName: 'edit_ui_block',

    /**
     * {@inheritdoc}
     */
    initialize: function (options) {
      this.fetch();
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
     * Set top offset.
     */
    setTopOffset: function (topOffset) {
      this.save({topOffset: topOffset});
    }
  });

}(Drupal, Backbone, jQuery));
