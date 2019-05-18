/**
 * @file
 * A Backbone view for the feadmin toolbar embedded toggle element.
 *
 * Sponsored by: www.freelance-drupal.com
 */

(function (Drupal, Backbone) {
  'use strict';

  Drupal.feaAdmin = Drupal.feaAdmin || {};

  /**
   * Backbone view for the feadmin toolbar menu toggle element.
   */
  Drupal.feaAdmin.toolbar.TooglerVisualView = Backbone.View.extend({

    /**
     * Custom data.
     */
    activeClass: 'is-active',

    /**
     * Dom elements events.
     */
    events: {
      click: 'collapse'
    },

    /**
     * {@inheritdoc}
     */
    initialize: function () {
      this.render();
      this.listenTo(this.model, 'change:isCollapsed', this.render);
    },

    /**
     * {@inheritdoc}
     */
    render: function () {
      this.$el.toggleClass(this.activeClass, this.model.get('isCollapsed'));
      return this;
    },

    /**
     * Toggle toolbar.
     */
    collapse: function () {
      this.model.collapse();
    }
  });

}(Drupal, Backbone));
