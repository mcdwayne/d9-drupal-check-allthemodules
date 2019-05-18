/**
 * @file
 * A Backbone view for the edit_ui trash element.
 */

(function (Drupal) {
  "use strict";

  var strings = {
    confirmDelete: Drupal.t('Are you sure you want to delete the block "@name"?')
  };

  /**
   * Backbone view for the edit_ui trash region.
   */
  Drupal.editUi.region.TrashVisualView = Drupal.editUi.region.RegionVisualView.extend({
    /**
     * Custom data.
     */
    dragClass: 'is-edit-ui-region-available',

    /**
     * Dom elements events.
     */
    events: function () {
      var events = {};
      var transitionEvent = this.whichTransitionEvent();
      if (transitionEvent) {
        events[transitionEvent] = 'calculateDimensions';
      }
      return events;
    },

    /**
     * Find the transition end event name depending on the browser.
     *
     * @return string
     *   The transition end event name.
     */
    whichTransitionEvent: function () {
      var el;
      var transition;
      var transitions = {
        'transition': 'transitionend',
        'OTransition': 'oTransitionEnd',
        'MozTransition': 'transitionend',
        'WebkitTransition': 'webkitTransitionEnd'
      };

      el = document.createElement('fakeelement');
      for (transition in transitions) {
        if (typeof el.style[transition] !== 'undefined') {
          return transitions[transition];
        }
      }
    },

    /**
     * {@inheritdoc}
     */
    initialize: function (options) {
      // Call parent method.
      Drupal.editUi.region.RegionVisualView.prototype.initialize.apply(this, arguments);

      // Add listeners.
      this.listenTo(this.model, 'stopDrag', this.stopDrag);

      // Initialize default.
      this.revertOnSpill = true;
    },

    /**
     * Initiliaze region when dragging.
     *
     * @param Drupal.editUi.block.BlockModel block
     *   The dragged block.
     */
    startDrag: function (block) {
      Drupal.editUi.region.RegionVisualView.prototype.startDrag.apply(this, arguments);
      if (!block.isNew()) {
        this.$el.addClass(this.dragClass);
      }
    },

    /**
     * Drag event callback.
     *
     * @param Drupal.editUi.block.BlockModel block
     *   The block model of the dragged element.
     * @param Object args
     *   The drag position.
     */
    drag: function (block, args) {
      if (!this.isInside(args)) {
        if (this.model.get('isActive')) {
          // Leave the region.
          this.model.deactivate();
        }
      }
      else {
        if (!this.model.get('isActive')) {
          // Enter the region.
          this.model.activate();
        }
      }
    },

    /**
     * Drop event callback.
     *
     * @param Drupal.editUi.block.BlockModel block
     *   The block model of the dropped element.
     */
    drop: function (block) {
      var message = Drupal.formatString(strings.confirmDelete, {'@name': block.get('label')});

      if (confirm(message)) {
        block.destroy({success: Drupal.editUi.ajax.callAjaxCommands});
      }
      else {
        this.model.deactivate();

        // Reset drag for all other regions (but this region).
        Drupal.editUi.region.collections.regionCollection.resetDrag(this.model);

        // Force block revert in this case.
        block.set('region', block.get('startRegion'));
        block.set('weight', block.get('startWeight'));
        Drupal.editUi.region.collections.regionCollection
          .getRegion(block.get('startRegion'))
          .trigger('addBlock', block, block.get('block'));
      }
    },

    /**
     * Remove class when drag is over.
     */
    stopDrag: function () {
      this.$el.removeClass(this.dragClass);
    },

    /**
     * Override and do nothing.
     */
    addBlock: function () {}
  });

}(Drupal));
