/**
 * @file
 * A Backbone Model for the edit_ui dragging block.
 */

(function (Drupal, Backbone) {

  "use strict";

  /**
   * Backbone model for the edit_ui dragging block.
   */
  Drupal.editUi.block.DraggingBlockModel = Backbone.Model.extend({
    defaults: {
      ghost: null,
      isVisible: false,
      offset: {
        top: 0,
        left: 0
      },
      dimensions: {
        width: 0,
        height: 0
      },
      draggingDimensions: {
        width: 0,
        height: 0
      },
      margins: {
        top: 0,
        bottom: 0
      }
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
    hide: function () {
      this.set({isVisible: false});
    },

    /**
     * Set ghost element.
     *
     * @param jQuery $ghost
     *   Ghost block element.
     */
    setGhost: function ($ghost) {
      this.set({ghost: $ghost});
    },

    /**
     * Set block offset.
     *
     * @param Object offset
     *   Block offset.
     */
    setOffset: function (offset) {
      this.set({offset: offset});
    },

    /**
     * Set block dimensions.
     *
     * @param Object dimensions
     *   Block dimensions.
     */
    setDimensions: function (dimensions) {
      this.set({dimensions: dimensions});
    },

    /**
     * Set block dragging dimensions.
     *
     * @param Object dimensions
     *   Block dragging dimensions.
     */
    setDraggingDimensions: function (dimensions) {
      this.set({draggingDimensions: dimensions});
    },

    /**
     * Set block margins.
     *
     * @param Object margins
     *   Block margins.
     */
    setMargins: function (margins) {
      this.set({margins: margins});
    }
  });

}(Drupal, Backbone));
