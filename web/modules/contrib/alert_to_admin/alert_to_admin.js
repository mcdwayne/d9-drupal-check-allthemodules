/**
 * @file
 * Alert to admin script for hide multiple alert on same page.
 */

(function ($) {
  Drupal.behaviors.admin_alert_message = {
    attach: function (context, settings) {
      if ($('div').find('.admin_alert_message').length > 1) {
        var div_count = $('div').find('.admin_alert_message').length;
        for (i = 0; i < div_count; i++) {
          $('div .admin_alert_message').eq(++i).hide();
        }
      }
    }
  };
}(jQuery));
