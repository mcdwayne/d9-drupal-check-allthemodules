/**
 * @file
 * A Backbone view for the feadmin_block block element.
 */

(function (Drupal, Backbone, $) {
  'use strict';

  Drupal.feaAdmin = Drupal.feaAdmin || {};

  /**
   * Backbone view for the edit_ui dragging block.
   */
  Drupal.feaAdmin.block.BlockView = Backbone.View.extend({

    /**
     * {@inheritdoc}
     */
    initialize: function (options) {
      // Add listeners.
      this.listenTo(this.model, 'destroy', this.remove);

      // Init model data.
      this.model.set('id', this.$el.data('block'));
    },

    /**
     * {@inheritdoc}
     */
    remove: function () {
      Backbone.View.prototype.remove.apply(this, arguments);
    }

  });

}(Drupal, Backbone, jQuery));
