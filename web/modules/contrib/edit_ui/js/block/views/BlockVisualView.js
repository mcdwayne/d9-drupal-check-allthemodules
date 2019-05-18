/**
 * @file
 * A Backbone view for the edit_ui block element.
 */

(function (Drupal, Backbone) {
  "use strict";

  /**
   * Backbone view for the edit_ui block.
   */
  Drupal.editUi.block.BlockVisualView = Backbone.View.extend({
    /**
     * Custom data.
     */
    unsavedClass: 'is-unsaved',

    /**
     * {@inheritdoc}
     */
    initialize: function (options) {
      // Add listeners.
      this.listenTo(this.model, 'change:content', this.render);
      this.listenTo(this.model, 'change:unsaved', this.toggleUnsaved);
      this.listenTo(this.model, 'destroy', this.remove);
      this.listenTo(this.model, 'calculateDimensions', this.calculateDimensions);

      // Init model state.
      this.model.setBlock(this.$el);
      this.model.show();
    },

    /**
     * {@inheritdoc}
     */
    render: function () {
      var content = this.model.get('content');
      if (content !== null) {
        if (content === '') {
          content = Drupal.theme('editUiBlockPlaceholderBlock', {
            id: this.model.get('id'),
            provider: this.model.get('provider'),
            label: this.model.get('label')
          });
        }

        // Insert new content.
        Drupal.detachBehaviors(this.$el.get(0));
        this.$el.html(content);
        Drupal.attachBehaviors(this.$el.get(0));

        // Trigger an event.
        this.model.trigger('contentChanged');
      }
    },

    /**
     * Toggle styles if block is not saved.
     */
    toggleUnsaved: function () {
      this.$el.toggleClass(this.unsavedClass, this.model.get('unsaved'));
    },

    /**
     * Calculate the block dimensions.
     */
    calculateDimensions: function () {
      var offset = this.$el.offset();

      // Save dimensions.
      this.model.setOffset({
        top: offset.top,
        left: offset.left
      });
      this.model.setDimensions({
        width: this.$el.outerWidth(),
        height: this.$el.outerHeight()
      });
    }
  });

}(Drupal, Backbone));
