/**
 * @file
 * A Backbone view for the edit_ui ghost block element.
 */

(function (Drupal, Backbone) {
  "use strict";

  /**
   * Backbone view for the edit_ui ghost block.
   */
  Drupal.editUi.block.GhostBlockVisualView = Backbone.View.extend({
    /**
     * Custom data.
     */
    hiddenClass: 'visually-hidden',

    /**
     * {@inheritdoc}
     */
    initialize: function (options) {
      // Add listeners.
      this.listenTo(this.model, 'change:dimensions', this.render);
      this.listenTo(this.model, 'change:margins', this.render);
      this.listenTo(this.model, 'change:isVisible', this.render);
      this.listenTo(this.model, 'change:isDragging', this.isDragging);
      this.listenTo(this.model, 'update', this.update);

      if (this.model instanceof Drupal.editUi.block.BlockModel) {
        this.$el.addClass('edit-ui__ghost--block');
      }
      else if (this.model instanceof Drupal.editUi.block.DraggingBlockModel) {
        this.$el.addClass('edit-ui__ghost--dragging-block');
      }

      // Init model data.
      this.model.setGhost(this.$el);
    },

    /**
     * {@inheritdoc}
     */
    render: function () {
      var dimensions = this.model.get('dimensions');
      var margins = this.model.get('margins');

      this.$el.css({
        height: dimensions.height,
        marginTop: margins.top,
        marginBottom: margins.bottom
      });
      this.$el.toggleClass(this.hiddenClass, !this.model.get('isVisible'));
    },

    /**
     * Toggle ghost block depending on Drupal.editUi.block.BlockModel:isDragging state.
     */
    isDragging: function () {
      if (!this.model instanceof Drupal.editUi.block.BlockModel) {
        return;
      }

      if (this.model.get('isDragging')) {
        this.insertAfter(this.model.get('block'));
      }
      else {
        this.detach();
      }
    },

    /**
     * Update block position depending on input.
     *
     * @param {jQuery|null} element
     *   jQuery DOM element.
     */
    update: function (element) {
      if (element) {
        this.insertAfter(element);
      }
      else {
        this.detach();
      }
    },

    /**
     * Insert ghost block.
     *
     * @param jQuery element
     *   jQuery DOM element.
     */
    insertAfter: function (element) {
      this.$el.insertAfter(element);
    },

    /**
     * Remove ghost block.
     */
    detach: function () {
      this.$el.detach();
    }
  });

}(Drupal, Backbone));
