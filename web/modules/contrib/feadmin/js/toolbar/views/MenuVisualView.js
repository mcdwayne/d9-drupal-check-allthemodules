/**
 * @file
 * A Backbone view for the feadmin toolbar menu toggle element.
 *
 * Sponsored by: www.freelance-drupal.com
 */

(function (Drupal, Backbone) {
  'use strict';

  Drupal.feaAdmin = Drupal.feaAdmin || {};

  /**
   * Backbone view for the feadmin toolbar menu toggle element.
   */
  Drupal.feaAdmin.toolbar.MenuVisualView = Backbone.View.extend({

    /**
     * Custom data.
     */
    activeClass: 'is-active',

    /**
     * Dom elements events.
     */
    events: {
      click: 'toggle'
    },

    /**
     * {@inheritdoc}
     */
    initialize: function () {
      this.render();
      this.listenTo(this.model, 'change:isOpen', this.render);
    },

    /**
     * {@inheritdoc}
     */
    render: function () {
      this.$el.toggleClass(this.activeClass, this.model.get('isOpen'));
      return this;
    },

    /**
     * Toggle toolbar.
     */
    toggle: function () {
      this.model.toggle();
    }
  });

}(Drupal, Backbone));
