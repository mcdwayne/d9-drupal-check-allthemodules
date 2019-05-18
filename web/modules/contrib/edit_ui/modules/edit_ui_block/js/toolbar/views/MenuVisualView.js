/**
 * @file
 * A Backbone view for the edit_ui menu element.
 */

(function (Drupal, Backbone) {
  "use strict";

  /**
   * Backbone view for the edit_ui menu.
   */
  Drupal.editUi.toolbar.MenuVisualView = Backbone.View.extend({
    /**
     * Custom data.
     */
    activeClass: 'is-active',

    /**
     * Dom elements events.
     */
    events: {
      "click": "toggle"
    },

    /**
     * {@inheritdoc}
     */
    initialize: function (options) {
      this.render();
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
      location.reload();
    }
  });

}(Drupal, Backbone));
