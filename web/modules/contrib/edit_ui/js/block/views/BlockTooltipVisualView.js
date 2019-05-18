/**
 * @file
 * A Backbone view for the edit_ui block's tooltip element.
 */

(function (Drupal, Backbone, $, Modernizr) {
  "use strict";

  /**
   * Backbone view for the edit_ui block's tooltip.
   */
  Drupal.editUi.block.BlockTooltipVisualView = Backbone.View.extend({
    /**
     * Dom elements events.
     */
    events: function () {
      var events = {};
      if (!Modernizr.touchevents) {
        events["mouseenter"] = "showTooltip";
        events["mouseleave"] = "hideTooltip";
      }
      return events;
    },

    /**
     * {@inheritdoc}
     */
    initialize: function (options) {
      // Add listeners.
      this.listenTo(this.model, 'contentChanged', this.render);

      // Render tooltip element.
      this.render();
    },

    /**
     * {@inheritdoc}
     */
    render: function () {
      this.$tooltip = $(Drupal.theme('editUiBlockTooltip', this.model.attributes));
      this.$tooltip.hide();
      this.$tooltip.appendTo(this.$el);
    },

    /**
     * Shows the block's tooltip.
     */
    showTooltip: function () {
      if (this.model.get('status')) {
        this.$tooltip.show();
        this.positionTooltip();
      }
    },

    /**
     * Hides the block's tooltip.
     */
    hideTooltip: function () {
      this.$tooltip.hide();
    },

    /**
     * Positions the tooltip.
     */
    positionTooltip: function () {
      var edge = (document.documentElement.dir === 'rtl') ? 'right' : 'left';
      Drupal.editUi.block.elements.editUiTooltipFence.css(Drupal.displace(false));

      this.$tooltip
        .position({
          my: edge + ' bottom',
          at: edge + ' top',
          of: this.$el,
          collision: 'flipfit',
          within: Drupal.editUi.block.elements.editUiTooltipFence
        })
        .css({
          'max-width': (document.documentElement.clientWidth < 450) ? document.documentElement.clientWidth : 450,
          'min-width': (document.documentElement.clientWidth < 240) ? document.documentElement.clientWidth : 240
        });

      if (this.$tooltip.position().top > 0) {
        this.$tooltip.addClass('edit-ui__tooltip--top');
      }
      else {
        this.$tooltip.removeClass('edit-ui__tooltip--top');
      }
    }

  });

}(Drupal, Backbone, jQuery, Modernizr));
