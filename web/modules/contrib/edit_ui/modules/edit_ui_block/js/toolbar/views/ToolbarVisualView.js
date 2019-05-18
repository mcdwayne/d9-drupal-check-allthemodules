/**
 * @file
 * A Backbone view for the edit_ui toolbar element.
 */

(function (Drupal, Backbone, $, Modernizr) {
  "use strict";

  /**
   * Backbone view for the edit_ui toolbar.
   */
  Drupal.editUi.toolbar.ToolbarVisualView = Backbone.View.extend({
    /**
     * Custom data.
     */
    hiddenClass: 'hidden',
    activeClass: 'active',

    /**
     * Dom elements events.
     */
    events: function () {
      var events = {
        'click .js-edit-ui__tabs__link': 'openTab',
        'click .js-edit-ui__toolbar__button': 'save'
      };
      if (Modernizr.touchevents) {
        events['touchstart .js-edit-ui__add-block'] = 'startDrag';
      }
      else {
        events['mousedown .js-edit-ui__add-block'] = 'startDrag';
      }
      return events;
    },

    /**
     * {@inheritdoc}
     */
    initialize: function (options) {
      // Get DOM elements.
      this.$tabLinks = $('.js-edit-ui__tabs__link');
      this.$tabContents = $('.js-edit-ui__tabs__content');
      this.$saveButton = $('.js-edit-ui__toolbar__button');

      // Add listeners.
      this.listenTo(this.model, 'change:topOffset', this.render);
      this.listenTo(Drupal.editUi.block.collections.blockCollection, 'change:unsaved', this.toggleButton);
      this.listenTo(Drupal.editUi.block.collections.blockCollection, 'destroy', this.toggleButton);
      $(document)
        .on('drupalViewportOffsetChange.editUiToolbarToolbarVisualView', function (event, offsets) {
          this.model.setTopOffset(offsets.top);
        }.bind(this));

      // Initialize AJAX links.
      $('.js-edit-ui__add-block__link', this.$el).each(function (index, link) {
        Drupal.editUi.ajax.initLinkAjax(link);
      });

      // Initialize default.
      this.openToolbar();
      this.$saveButton.attr('disabled', 'disabled');
    },

    /**
     * {@inheritdoc}
     */
    remove: function () {
      $(document).off('.editUiToolbarToolbarVisualView');
      Backbone.View.prototype.remove.apply(this, arguments);
    },

    /**
     * {@inheritdoc}
     */
    render: function () {
      this.$el.css({top: this.model.get('topOffset')});
      return this;
    },

    /**
     * Open the toolbar.
     */
    openToolbar: function () {
      var edge = (document.documentElement.dir === 'rtl') ? 'left' : 'right';
      this.$el.toggleClass(this.hiddenClass, !this.model.get('isOpen'));
      this.$el.attr('data-offset-' + edge, '');
    },

    /**
     * Open triggered tab.
     *
     * @param Event event
     *   The event object.
     */
    openTab: function (event) {
      event.preventDefault();
      var $target = $(event.target);
      var $content = $($target.attr('href'));

      this.$tabLinks.removeClass(this.activeClass);
      $target.addClass(this.activeClass);

      this.$tabContents.addClass(this.hiddenClass);
      $content.removeClass(this.hiddenClass);
    },

    /**
     * Start drag.
     *
     * @param Event event
     *   The event object.
     */
    startDrag: function (event) {
      var block;

      if (event.type === 'mousedown' && event.button !== 0) {
        return;
      }
      event.preventDefault();

      // Calculate all dimensions when drag start.
      Drupal.editUi.utils.calculateDimensions();

      // Create model.
      this.$target = $(event.target).closest('.js-edit-ui__add-block');
      block = new Drupal.editUi.block.BlockModel();
      Drupal.editUi.block.models.newBlockModel = block;
      Drupal.editUi.block.collections.blockCollection.add(block);

      // Update model state.
      block.startDrag();

      // Add listener.
      this.listenTo(block, 'change:isDragging', this.stopDrag);

      // Add classes.
      this.$target.addClass(this.activeClass);

      // Trigger startDrag event.
      this.$el.trigger("startDrag", [
        block,
        Drupal.editUi.utils.getPosition(event),
        {width: this.$target.outerWidth(), height: this.$target.outerHeight()},
        this.$target.offset()
      ]);
    },

    /**
     * Stop drag.
     *
     * @param Drupal.editUi.block.BlockModel block
     *   The dropped block.
     */
    stopDrag: function (block) {
      var ajaxInstance;
      var ajaxUrl;

      // Remove listener.
      this.stopListening(block);

      // Remove classes.
      this.$target.removeClass(this.activeClass);

      if (!block.get('region')) {
        // Block dropped outside a region.
        block.destroy();
        Drupal.editUi.block.models.newBlockModel = null;
      }
      else {
        // Block dropped inside a region.
        ajaxInstance = this.$target.find('.js-edit-ui__add-block__link').data('edit_ui-ajax');

        // Add custom data.
        ajaxUrl = ajaxInstance.options.url;
        if (ajaxInstance.options.url.indexOf('?') === -1) {
          ajaxInstance.options.url += '?';
        }
        else {
          ajaxInstance.options.url += '&';
        }
        ajaxInstance.options.url += 'region=' + block.get('region');
        ajaxInstance.options.url += '&weight=' + block.get('weight');

        // Execute AJAX.
        ajaxInstance.execute();

        // Reset instance URL.
        ajaxInstance.options.url = ajaxUrl;
      }
    },

    /**
     * Save button clicked.
     *
     * @param Event event
     *   The event object.
     */
    save: function (event) {
      event.preventDefault();
      Drupal.editUi.block.collections.blockCollection.save();
    },

    /**
     * Toggle the button.
     */
    toggleButton: function (block) {
      if (Drupal.editUi.block.collections.blockCollection.hasUnsavedChanges()) {
        this.$saveButton.removeAttr('disabled');
      }
      else {
        this.$saveButton.attr('disabled', 'disabled');
      }
    }
  });

}(Drupal, Backbone, jQuery, Modernizr));
