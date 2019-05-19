/**
 * @file
 * Smartlook admin behaviors.
 */

(function ($, window) {
  "use strict";

  /**
   * Provide the summary information for the tracking settings vertical tabs.
   */
  Drupal.behaviors.trackingSettingsSummary = {
    attach: function () {
      // Make sure this behavior is processed only if drupalSetSummary is
      // defined.
      if (typeof jQuery.fn.drupalSetSummary === 'undefined') {
        return;
      }

      $('#edit-page-visibility-settings').drupalSetSummary(function (context) {
        var $radio = $('input[name="smartlook_tracking_visibility_request_path_mode"]:checked', context);

        if ($radio.val() === '0') {
          if (!$('textarea[name="smartlook_tracking_visibility_request_path_pages"]', context).val()) {
            return Drupal.t('Not restricted');
          }
          else {
            return Drupal.t('All pages with exceptions');
          }
        }
        else {
          return Drupal.t('Restricted to certain pages');
        }
      });
    }
  };

})(jQuery, window);
