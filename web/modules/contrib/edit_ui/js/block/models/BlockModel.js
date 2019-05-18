/**
 * @file
 * A Backbone Model for the edit_ui block.
 */

(function (Drupal, Backbone) {

  "use strict";

  /**
   * Backbone model for the edit_ui block.
   */
  Drupal.editUi.block.BlockModel = Backbone.Model.extend({
    defaults: {
      content: null,
      plugin_id: false,
      region: false,
      weight: 0,
      label: '',
      status: 1,
      html_id: '',
      block: null,
      ghost: null,
      isVisible: false,
      isDragging: false,
      offset: {
        top: 0,
        left: 0
      },
      dimensions: {
        width: 0,
        height: 0
      },
      margins: {
        top: 0,
        bottom: 0
      },
      unsaved: false
    },

    /**
     * {@inheritdoc}
     */
    urlRoot: Drupal.url('edit-ui/block'),

    /**
     * {@inheritdoc}
     */
    toJSON: function () {
      return {
        region: this.get('region'),
        weight: this.get('weight'),
        status: this.get('status'),
        visibility: this.get('visibility')
      };
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
     * Set block element.
     *
     * @param jQuery $block
     *   Block element.
     */
    setBlock: function ($block) {
      this.set({block: $block});
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
     * Set block margins.
     *
     * @param Object margins
     *   Block margins.
     */
    setMargins: function (margins) {
      this.set({margins: margins});
    },

    /**
     * Set block content.
     *
     * @param String content
     *   Block content.
     */
    setContent: function (content) {
      this.set({content: content});
    }
  });

}(Drupal, Backbone));
