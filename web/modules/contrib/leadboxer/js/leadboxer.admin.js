/**
 * @file
 * LeadBoxer admin behaviors.
 */

(function ($, window) {

  "use strict";

  /**
   * Provide the summary information for the tracking settings vertical tabs.
   */
  Drupal.behaviors.trackingSettingsSummary = {
    attach: function () {
      // Make sure this behavior is processed only if drupalSetSummary is defined.
      if (typeof jQuery.fn.drupalSetSummary === 'undefined') {
        return;
      }

      $('#edit-page-visibility-settings').drupalSetSummary(function (context) {
        var $radio = $('input[name="leadboxer_visibility_request_path_mode"]:checked', context);
        if ($radio.val() === '0') {
          if (!$('textarea[name="leadboxer_visibility_request_path_pages"]', context).val()) {
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

      $('#edit-role-visibility-settings').drupalSetSummary(function (context) {
        var vals = [];
        $('input[type="checkbox"]:checked', context).each(function () {
          vals.push($.trim($(this).next('label').text()));
        });
        if (!vals.length) {
          return Drupal.t('Not restricted');
        }
        else if ($('input[name="leadboxer_visibility_user_role_mode"]:checked', context).val() === '1') {
          return Drupal.t('Excepted: @roles', {'@roles': vals.join(', ')});
        }
        else {
          return vals.join(', ');
        }
      });
    }
  };

})(jQuery, window);
