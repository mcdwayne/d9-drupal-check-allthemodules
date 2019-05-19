/**
 * @file
 * Contains online_status.js.
 */
(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.UserOnlineStatus = {
    attach: function (context, settings) {

      $('.user-online-status', context).each(function () {

        var uid = $(this).attr('data-user-online-status-uid');
        var $status = $(this).find('.status');
        var $response = $(this).find('.response');

        // Check status.
        $.getJSON('/online-status/' + uid, function (result) {

          if (result.online_status) {
            printOnlineStatus(result.online_status);
          }

        });

        // Print status.
        var printOnlineStatus = function (status) {
          $status.addClass(status);
          switch (status) {
            case 'online':
              $response.html(Drupal.t('online'));
              break;
            case 'absent':
              $response.html(Drupal.t('absent'));
              break;
            case 'offline':
              $response.html(Drupal.t('offline'));
              break;
            default:
              console.log('user_online_status status not found');
          }
        }

      });

    }
  };
})(jQuery, Drupal);
