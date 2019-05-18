/**
 * @file
 * A Backbone view for the edit_ui body element.
 */

(function (Drupal, Backbone, $, Modernizr) {
  "use strict";

  /**
   * Backbone view for the edit_ui body.
   */
  Drupal.editUi.block.BodyVisualView = Backbone.View.extend({
    /**
     * Custom data.
     */
    draggingClass: 'is-edit-ui-body-dragging',

    /**
     * Main element.
     */
    el: 'body',

    /**
     * Dom elements events.
     */
    events: {
      "startDrag": "startDrag"
    },

    /**
     * {@inheritdoc}
     */
    initialize: function (options) {
      // Init variables.
      this.$window = $(window);

      // Add listeners.
      this.listenTo(this.collection, 'change:isDragging', this.render);
    },

    /**
     * {@inheritdoc}
     */
    remove: function () {
      this.$window.off('.editUiBlockBodyVisualView');
      Backbone.View.prototype.remove.apply(this, arguments);
    },

    /**
     * {@inheritdoc}
     */
    render: function (block) {
      this.$el.toggleClass(this.draggingClass, block.get('isDragging'));
    },

    /**
     * Start drag.
     *
     * @param Event event
     *   The event object.
     * @param Drupal.editUi.block.BlockModel block
     *   The block model of the dragged element.
     * @param Object position
     *   The mouse or touch position.
     * @param Object dimensions
     *   Original element dimensions.
     * @param Object offset
     *   Original element offset.
     */
    startDrag: function (event, block, position, dimensions, offset) {
      // Initialize drag for regions.
      Drupal.editUi.region.collections.regionCollection.startDrag(block);

      // Select start region.
      var region = Drupal.editUi.region.collections.regionCollection.getRegion(block.get('region'));
      if (region) {
        region.select();
      }

      // Add listeners.
      if (Modernizr.touchevents) {
        this.$window.on('touchmove.editUiBlockBodyVisualView', $.proxy(this.drag, this, block));
        this.$window.on('touchend.editUiBlockBodyVisualView', $.proxy(this.drop, this, block));
      }
      else {
        this.$window.on('mousemove.editUiBlockBodyVisualView', $.proxy(this.drag, this, block));
        this.$window.on('mouseup.editUiBlockBodyVisualView', $.proxy(this.drop, this, block));
      }
    },

    /**
     * Drag.
     *
     * @param Drupal.editUi.block.BlockModel block
     *   The dropped block.
     * @param Event event
     *   The event object.
     */
    drag: function (block, event) {
      var position;
      event.preventDefault();

      // Dragging block styles.
      position = Drupal.editUi.utils.getPosition(event);
      Drupal.editUi.block.models.draggingBlockModel.setOffset({
        top: position.y,
        left: position.x
      });

      // Propagates drag events to region collection.
      Drupal.editUi.region.collections.regionCollection.drag(block, position.x, position.y);
    },

    /**
     * Drop.
     *
     * @param Drupal.editUi.block.BlockModel block
     *   The dropped block.
     * @param Event event
     *   The event object.
     */
    drop: function (block, event) {
      // Propagates drop events to region collection.
      var position = Drupal.editUi.utils.getPosition(event);
      Drupal.editUi.region.collections.regionCollection.drop(block, position.x, position.y);

      // Remove listeners.
      this.$window.off('.editUiBlockBodyVisualView');

      // Update model state.
      block.stopDrag();

      // Trigger stopDrag event.
      Drupal.editUi.block.models.draggingBlockModel.trigger('stopDrag', block, position);

      // Reset default states.
      Drupal.editUi.utils.reset();
    }
  });

}(Drupal, Backbone, jQuery, Modernizr));
