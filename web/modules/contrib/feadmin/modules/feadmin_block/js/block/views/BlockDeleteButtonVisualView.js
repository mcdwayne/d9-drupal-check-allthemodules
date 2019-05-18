/**
 * @file
 * A Backbone view for the feadmin_block block element.
 *
 * Sponsored by: www.freelance-drupal.com
 */

(function (Drupal, Backbone, $) {
  'use strict';

  Drupal.feaAdmin = Drupal.feaAdmin || {};

  var strings = {
    confirmDelete: Drupal.t('Are you sure you want to delete the block "@name"?')
  };

  /**
   * Backbone view for the feadmin block.
   */
  Drupal.feaAdmin.block.BlockDeleteButtonVisualView = Backbone.View.extend({

    /**
     * Dom elements events.
     */
    events: {
      click: 'deleteBlock'
    },

    /**
     * {@inheritdoc}
     */
    initialize: function () {
    },

    /**
     * Delete block.
     *
     * @param event
     *   the Event object.
     */
    deleteBlock: function (event) {
      event.preventDefault();
      var block = $(this.$el).closest('[data-block]').data('block');
      var region = $(this.$el).closest('[data-region]');
      var message = Drupal.formatString(strings.confirmDelete, {'@name': block});

      if (confirm(message)) {

        // The origin region may now be empty.
        if ($('[data-block]:not(".empty-block")', region).length === 0) {
          region.addClass('empty-region');
        }
        var body = $('body');
        // Remove all sidebars configurations on body.
        body.removeClass(function (index, css) {
          return (css.match(/(^|\s)layout-\S+/g) || []).join(' ');
        });
        // Count number of sidebar columns now filled.
        var sidebarsCount;
        var sidebars = $('[data-region*="sidebar"]:not(".empty-region")');
        if (sidebars.length) {
          sidebarsCount = (sidebars.length === 1) ? 'one' : 'two';
        }
        else {
          sidebarsCount = 'no';
        }
        // Add body general sidebar configurations.
        body.addClass('layout-' + sidebarsCount + '-sidebar' + ((sidebars.length === 2) ? 's' : ''));
        // Add specific sidebars
        if (sidebars.length === 1) {
          // Find the sidebar name.
          body.addClass('layout-' + sidebars.data('region').replace('_', '-'));
        }

        var request = {};

        // Build destination region
        request['deleted'] = block;

        // Send the changed data to our backend.
        var uniqId = _uniqId();
        $.ajax({
          type: 'DELETE',
          url: Drupal.url('feadmin/block/' + block + '/delete'),
          contentType: 'application/json',
          data: JSON.stringify(request),
          beforeSend: function () {
            $.notify(Drupal.t('Block is being deleting...'), {
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
            $('[data-block="' + block + '"]').remove();
          },
          error: function () {
            $('.notifyjs-bootstrap-' + uniqId).trigger('notify-hide');
            $.notify(Drupal.t('An error has happened: block is not deleted.'), {
              className: 'error',
              position: 'left bottom'
            });
          }
        });
      }
    }
  });

  // Should work for most cases
  var _uniqId = function () {
    return Math.round(new Date().getTime() + (Math.random() * 100));
  };

}(Drupal, Backbone, jQuery));
