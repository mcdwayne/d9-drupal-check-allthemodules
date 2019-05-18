/**
 * @file
 * A Backbone view for the edit_ui menu element.
 */

(function (Drupal, Backbone) {
  "use strict";

  /**
   * Backbone view for the edit_ui body.
   */
  Drupal.editUi.toolbar.BodyVisualView = Backbone.View.extend({
    /**
     * Custom data.
     */
    activeClass: 'is-edit-ui-toolbar-opened',

    /**
     * Main element.
     */
    el: 'body',

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
    }
  });

}(Drupal, Backbone));
