/**
 * @file
 * A Backbone view for the edit_ui block element.
 */

(function (Drupal, Modernizr) {
  "use strict";

  /**
   * This is a list of plugin id to be excluded from ajax refresh because the
   * content of the block depends on the page context (e.g. route...etc.).
   *
   * @type Array
   */
  var PLUGIN_ID_LIST_TO_EXCLUDE = [
    'system_main_block',
    'page_title_block',
    'local_tasks_block',
    'local_actions_block'
  ];

  /**
   * Custom data.
   */
  Drupal.editUi.block.BlockVisualView.prototype.dragClass = 'is-dragging';
  Drupal.editUi.block.BlockVisualView.prototype.isGrabbed = false;

  /**
   * Dom elements events.
   */
  Drupal.editUi.block.BlockVisualView.prototype.events = function () {
    var events = {};
    if (Modernizr.touchevents) {
      events["touchstart"] = "grab";
      events["touchend"] = "release";
      events["touchmove"] = "moveStart";
      events["touchstart .contextual"] = function (e) {e.stopPropagation(); };
    }
    else {
      events["mousedown"] = "grab";
      events["mouseup"] = "release";
      events["mousemove"] = "moveStart";
      events["mousedown .contextual"] = function (e) {e.stopPropagation(); };
    }
    return events;
  };

  /**
   * {@inheritdoc}
   */
  var parentInitialize = Drupal.editUi.block.BlockVisualView.prototype.initialize;
  Drupal.editUi.block.BlockVisualView.prototype.initialize = function (options) {
    parentInitialize.bind(this)(options);

    // Add listeners.
    this.listenTo(this.model, 'change:isDragging', this.toggleDrag);
  };

  /**
   * Toggle styles when dragging.
   */
  Drupal.editUi.block.BlockVisualView.prototype.toggleDrag = function () {
    this.$el.toggleClass(this.dragClass, this.model.get('isDragging'));
    if (!this.model.get('isDragging')) {
      this.release();

      // Check if the block has been moved.
      if (this.model.get('region') !== this.model.get('startRegion') ||
        this.model.get('weight') !== this.model.get('startWeight')) {
        if (this.model.get('region') === '-1') {
          // Special content for disabled and some specific blocks.
          this.model.set(
            'content',
            Drupal.theme('editUiBlockDisabledBlock', this.model.attributes)
          );
        }
        else if (Number(PLUGIN_ID_LIST_TO_EXCLUDE.indexOf(this.model.get('plugin_id'))) === -1 ||
          Number(this.model.get('startRegion')) === -1) {
          // Get updated content (because it may be different from one region to another).
          this.model.fetch();
        }
      }
    }
  };

  /**
   * Block is grabbed.
   *
   * @param Event event
   *   The event object.
   */
  Drupal.editUi.block.BlockVisualView.prototype.grab = function (event) {
    if (Drupal.editUi.utils.whichMouseButton(event) !== 1 || event.metaKey || event.ctrlKey) {
      return;
    }

    if (event.type === 'mousedown') {
      if (Drupal.editUi.utils.isInput(event.target)) {
        // Focus input event.
        event.target.focus();
      }
      else {
        // Avoid the text selection.
        event.preventDefault();
      }
    }

    this.isGrabbed = true;
  };

  /**
   * Block is released.
   */
  Drupal.editUi.block.BlockVisualView.prototype.release = function () {
    this.isGrabbed = false;
  };

  /**
   * Drag may start if the block was grabbed and not already grabbing.
   *
   * @param Event event
   *   The event object.
   */
  Drupal.editUi.block.BlockVisualView.prototype.moveStart = function (event) {
    if (!this.isGrabbed || this.model.get('isDragging')) {
      return;
    }
    this.startDrag(Drupal.editUi.utils.getPosition(event));
  };

  /**
   * Start drag.
   *
   * @param object position
   *   The mouse or touch position.
   */
  Drupal.editUi.block.BlockVisualView.prototype.startDrag = function (position) {
    // Calculate all dimensions.
    Drupal.editUi.utils.calculateDimensions();

    // Update model state.
    this.model.startDrag();

    // Trigger events.
    this.$el.trigger('startDrag', [
      this.model,
      position,
      this.model.get('dimensions'),
      this.model.get('offset')
    ]);
  };

}(Drupal, Modernizr));
