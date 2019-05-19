/**
 * @file
 * Attach behaviour for the Site Notifications Module.
 *
 * Sends request and gets response and updates
 * notification block contents asynchronousely.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Attaches the JS refresh_interval behavior.
   */
  Drupal.behaviors.site_notifications = {
    attach: function (context, settings) {
      var refresh_interval = drupalSettings.site_notifications.refresh_interval;
      var notify_status = drupalSettings.site_notifications.notify_status;
      var user_access = drupalSettings.site_notifications.user_access;

      $(document).ready(function (e) {
        if (notify_status === 1 && refresh_interval != null && refresh_interval !== '' && user_access === 1) {
          setInterval(function () {
            site_notification_block_refresh();
          }, refresh_interval);
        }
        if (notify_status === 0) {
          $('div#block-sitenotifications').hide();
        }
      });

      /**
       * Implements: site_notification_block_refresh.
       *
       * Refresh notification block contents with refresh_interval.
       */
      function site_notification_block_refresh() {
        $.ajax({
          url: Drupal.url('notification/updates'),
          type: 'POST',
          data: {request: 'ajax'},
          dataType: 'json',
          async: true,
          success: function (data) {
            var title = 'Notifications (' + data.count + ')';
            $('div#block-sitenotifications h2').html(title);

            $('div.notification_block').html(data.output_inner);
          }
        });
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
