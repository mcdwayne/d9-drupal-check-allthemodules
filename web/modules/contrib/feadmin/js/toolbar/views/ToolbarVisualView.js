/**
 * @file
 * A Backbone view for the feadmin toolbar element.
 *
 * Sponsored by: www.freelance-drupal.com
 */

(function (Drupal, Backbone, $) {
  'use strict';

  Drupal.feaAdmin = Drupal.feaAdmin || {};

  /**
   * Backbone view for the feadmin toolbar.
   */
  Drupal.feaAdmin.toolbar.ToolbarVisualView = Backbone.View.extend({

    /**
     * Custom data.
     */
    hiddenClass: 'hidden',
    collapsedClass: 'collapsed',
    activeClass: 'active',

    /**
     * {@inheritdoc}
     */
    initialize: function () {

      var that = this;

      // Get DOM elements.
      this.$accordions = $('.toolbar-tray-content');

      // Add listeners.
      this.listenTo(this.model, 'change:topOffset', this.render);
      this.listenTo(this.model, 'change:isOpen', this.openToolbar);
      this.listenTo(this.model, 'change:isCollapsed', this.collapseToolbar);

      // Initialize default.
      if (Drupal.feaAdmin.toolbar.views.tooglerVisualView) {
        this.collapseToolbar();
      }
      if (Drupal.feaAdmin.toolbar.views.menuVisualView) {
        this.openToolbar();
      }

      // Initialize accordion tools.
      this.$accordions.accordion({
        icons: {
          header: 'toolbar-icon-disabled',
          activeHeader: 'toolbar-icon-enabled'
        },
        collapsible: true,
        active: false,
        heightStyle: 'panel',
        header: 'h3',
        activate: function (event, ui) {
          that.model.activateTool($(ui.newHeader).data('toolbar-tool'));
          event.stopPropagation();
        },
        beforeActivate: function (event, ui) {
          if ($(ui.newHeader).hasClass('ui-state-disabled')) {
            var oldIndex = $(ui.oldHeader).index('h3');
            $(this).accordion('activate', oldIndex);
          }
        }
      });
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
     * Open the toolbar.
     */
    collapseToolbar: function () {
      this.$el.toggleClass(this.collapsedClass, !this.model.get('isCollapsed'));
    }

  });

}(Drupal, Backbone, jQuery));
