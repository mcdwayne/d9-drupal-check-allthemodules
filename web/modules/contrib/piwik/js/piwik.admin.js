/**
 * @file
 * Piwik admin behaviors.
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
        var $radio = $('input[name="piwik_visibility_request_path_mode"]:checked', context);
        if ($radio.val() === '0') {
          if (!$('textarea[name="piwik_visibility_request_path_pages"]', context).val()) {
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
        else if ($('input[name="piwik_visibility_user_role_mode"]:checked', context).val() === '1') {
          return Drupal.t('Excepted: @roles', {'@roles': vals.join(', ')});
        }
        else {
          return vals.join(', ');
        }
      });

      $('#edit-user-visibility-settings').drupalSetSummary(function (context) {
        var $radio = $('input[name="piwik_visibility_user_account_mode"]:checked', context);
        if ($radio.val() === '0') {
          return Drupal.t('Not customizable');
        }
        else if ($radio.val() === '1') {
          return Drupal.t('On by default with opt out');
        }
        else {
          return Drupal.t('Off by default with opt in');
        }
      });

      $('#edit-linktracking').drupalSetSummary(function (context) {
        var vals = [];
        if ($('input#edit-piwik-trackmailto', context).is(':checked')) {
          vals.push(Drupal.t('Mailto links'));
        }
        if ($('input#edit-piwik-trackfiles', context).is(':checked')) {
          vals.push(Drupal.t('Outbound links'));
          vals.push(Drupal.t('Downloads'));
        }
        if ($('input#edit-piwik-trackcolorbox', context).is(':checked')) {
          vals.push(Drupal.t('Colorbox'));
        }
        if (!vals.length) {
          return Drupal.t('Not tracked');
        }
        return Drupal.t('@items enabled', {'@items': vals.join(', ')});
      });

      $('#edit-messagetracking').drupalSetSummary(function (context) {
        var vals = [];
        $('input[type="checkbox"]:checked', context).each(function () {
          vals.push($.trim($(this).next('label').text()));
        });
        if (!vals.length) {
          return Drupal.t('Not tracked');
        }
        return Drupal.t('@items enabled', {'@items': vals.join(', ')});
      });

      $('#edit-search').drupalSetSummary(function (context) {
        var vals = [];
        if ($('input#edit-piwik-site-search', context).is(':checked')) {
          vals.push(Drupal.t('Site search'));
        }
        if (!vals.length) {
          return Drupal.t('Not tracked');
        }
        return Drupal.t('@items enabled', {'@items': vals.join(', ')});
      });

      $('#edit-domain-tracking').drupalSetSummary(function (context) {
        var $radio = $('input[name="piwik_domain_mode"]:checked', context);
        if ($radio.val() === '0') {
          return Drupal.t('A single domain');
        }
        else if ($radio.val() === '1') {
          return Drupal.t('One domain with multiple subdomains');
        }
      });

      $('#edit-privacy').drupalSetSummary(function (context) {
        var vals = [];
        if ($('input#edit-piwik-privacy-donottrack', context).is(':checked')) {
          vals.push(Drupal.t('Universal web tracking opt-out'));
        }
        if (!vals.length) {
          return Drupal.t('No privacy');
        }
        return Drupal.t('@items enabled', {'@items': vals.join(', ')});
      });
    }
  };

})(jQuery, window);
