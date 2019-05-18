/**
 * @file
 * A Backbone view for the body when feaadmin toobar is rendered.
 *
 * Sponsored by: www.freelance-drupal.com
 */

(function (Drupal, Backbone) {
  'use strict';

  Drupal.feaAdmin = Drupal.feaAdmin || {};

  /**
   * Backbone view for the body when feadmin toolbar is rendered.
   */
  Drupal.feaAdmin.toolbar.BodyVisualView = Backbone.View.extend({

    /**
     * Custom data.
     */
    activeClass: 'feadmin-toolbar-opened',

    /**
     * Main element.
     */
    el: 'body',

    /**
     * {@inheritdoc}
     */
    initialize: function () {
      this.listenTo(this.model, 'change:isOpen', this.render);
      this.listenTo(this.model, 'change:isCollapsed', this.render);
      this.render();
    },

    /**
     * {@inheritdoc}
     */
    render: function () {
      if (Drupal.feaAdmin.toolbar.views.tooglerVisualView) {
        this.$el.toggleClass(this.activeClass, this.model.get('isCollapsed'));
      }
      if (Drupal.feaAdmin.toolbar.views.menuVisualView) {
        this.$el.toggleClass(this.activeClass, this.model.get('isOpen'));
      }
      return this;
    }

  });

}(Drupal, Backbone));
