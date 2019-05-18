/**
 * @file
 * A Backbone view for the edit_ui dragging block element.
 */

(function (Drupal, Backbone, $) {
  "use strict";

  /**
   * Backbone view for the edit_ui dragging block.
   */
  Drupal.editUi.block.DraggingBlockVisualView = Backbone.View.extend({
    /**
     * {@inheritdoc}
     */
    initialize: function (options) {
      // Add listeners.
      this.listenTo(this.model, 'change:offset', this.render);
      this.listenTo(this.model, 'stopDrag', this.stopDrag);
      $(window).on('startDrag.editUiBlockDraggingBlockView', $.proxy(this.startDrag, this));

      // Init model data.
      this.model.setDraggingDimensions({
        width: this.$el.outerWidth(),
        height: this.$el.outerHeight()
      });
    },

    /**
     * {@inheritdoc}
     */
    render: function () {
      var dimensions = this.model.get('draggingDimensions');
      var offset = this.model.get('offset');

      this.$el.css({
        top: offset.top - dimensions.height / 2,
        left: offset.left - dimensions.width / 2
      });
    },

    /**
     * {@inheritdoc}
     */
    remove: function () {
      $(window).off('.editUiBlockDraggingBlockView');
      Backbone.View.prototype.remove.apply(this, arguments);
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
      var draggingDimensions = this.model.get('draggingDimensions');

      this.$el.css({
        width: dimensions.width,
        height: dimensions.height,
        top: position.y - draggingDimensions.height / 2,
        left: position.x - draggingDimensions.width / 2,
        marginTop: -position.y + offset.top + draggingDimensions.height / 2,
        marginLeft: -position.x + offset.left + draggingDimensions.width / 2,
        borderRadius: 0,
        opacity: 0
      });
      this.$el.show();

      // Start dragging animation.
      setTimeout(this.resetStyle.bind(this), 0);

      // Update model data.
      this.model.setDimensions({
        width: dimensions.width,
        height: dimensions.height
      });
      this.model.show();
    },

    /**
     * Stop drag.
     */
    stopDrag: function () {
      this.$el.hide();

      // Update model data.
      this.model.hide();
    },

    /**
     * Reset dragging block style.
     */
    resetStyle: function () {
      this.$el.css({
        width: '',
        height: '',
        marginTop: '',
        marginLeft: '',
        borderRadius: '',
        opacity: ''
      });
    }
  });

}(Drupal, Backbone, jQuery));
