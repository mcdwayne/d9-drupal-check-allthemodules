/**
 * @file
 * A Backbone view for the body when feaadmin toobar is rendered.
 * Sponsored by: www.freelance-drupal.com
 */

(function (Drupal, Backbone, $) {
  'use strict';

  Drupal.feaAdmin = Drupal.feaAdmin || {};

  /**
   * Backbone view for the body when feadmin toolbar is rendered.
   */
  Drupal.feaAdmin.menu.BodyVisualView = Backbone.View.extend({

    /**
     * Main element.
     */
    el: 'body',

    /**
     * Regions within this body
     */
    menus: null,

    /**
     * {@inheritdoc}
     */
    initialize: function () {
      this.menus = $('[data-menu]');
      this.listenTo(this.model, 'change:activeTool', this.drag);
    },

    /**
     * {@inheritdoc}
     */
    drag: function () {
      var activeTool = this.model.get('activeTool');
      if (activeTool === 'feadmin_menu') {
        this.startDrag();
      }
      else {
        this.stopDrag();
      }
    },

    /**
     * Start dragging blocks around !
     */
    startDrag: function () {
      // Regions are sortable: blocks can move around.
      $('[data-menu-item]').addClass('draggable');
      this.menus.sortable({
        items: '[data-menu-item]',
        placeholder: 'menu-placeholder ui-state-highlight',

        // Display some menu item placeholder indicators.
        sort: function () {
          $('.menu-placeholder').text(Drupal.t('Drop here'));
        },

        // Update the menu orders after the dragging was completed.
        update: function (event, ui) {
          if (this === ui.item.parent()[0]) {

            var destinationMenuName = $(this).data('menu');
            var request = {};

            // Build destination region
            request['menu'] = destinationMenuName;
            request['menu_items'] = [];
            $('[data-menu-item]', $(this)).each(function () {
              request['menu_items'].push($(this).data('menu-item'));
            });

            // Send the changed data to our backend.
            var uniqId = _uniqId();
            $.ajax({
              type: 'POST',
              url: Drupal.url('feadmin/callback/menus'),
              contentType: 'application/json',
              data: JSON.stringify(request),
              beforeSend: function () {
                $.notify(Drupal.t('Menu items are saving...'), {
                  className: ['wait', uniqId],
                  position: 'left bottom'
                });
              },
              success: function (data) {
                $('.notifyjs-bootstrap-' + uniqId).trigger('notify-hide');
                $.notify(data, {
                  className: 'success',
                  position: 'left bottom'
                });
              },
              error: function () {
                $('.notifyjs-bootstrap-' + uniqId).trigger('notify-hide');
                $.notify(Drupal.t('An error has happened: menu items order is not saved.'), {
                  className: 'error',
                  position: 'left bottom'
                });
              }
            });
          }
        }
      });
    },

    stopDrag: function () {
      $('[data-menu-item]').removeClass('draggable');
      if (this.menus.data('ui-sortable')) {
        this.menus.sortable('destroy');
      }
    }

  });

  // Should work for most cases
  var _uniqId = function () {
    return Math.round(new Date().getTime() + (Math.random() * 100));
  };
}(Drupal, Backbone, jQuery));
