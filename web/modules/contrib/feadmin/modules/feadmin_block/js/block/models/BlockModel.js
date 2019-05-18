/**
 * @file
 * A Backbone Model for the feadmin_block.
 */

(function (Drupal, Backbone, $) {
  'use strict';

  

  /**
   * Backbone model for the feadmin_block.
   */
  Drupal.feaAdmin.block.BlockModel = Backbone.Model.extend({
    defaults: {
      id: null
    },

    /**
     * Custom data.
     */
    cookieName: 'feadmin',

    /**
     * {@inheritdoc}
     */
    initialize: function () {
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
      return {
        id: this.get('id')
      };
    },

    /**
     * Set block visible state.
     */
    show: function () {
      this.set({isVisible: true});
    },

    /**
     * Set block visible state.
     */
    delete: function () {
      this.destroy();
    },

    remove: function () {
      this.undelegateEvents();
      this.$el.empty();
      this.stopListening();
      return this;
    }

  });

}(Drupal, Backbone, jQuery));
