/**
 * @file
 * Provides JavaScript additions to the managed Ip Address field.
 */

(function ($, window, Drupal) {

  'use strict';

  /**
   * Provide the summary information for the block settings vertical tabs.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the block settings summaries.
   */
  Drupal.behaviors.blockSettingsSummaryIpAddress = {
    attach() {
      // The drupalSetSummary method required for this behavior is not available
      // on the Blocks administration page, so we need to make sure this
      // behavior is processed only if drupalSetSummary is defined.
      if (typeof $.fn.drupalSetSummary === 'undefined') {
        return;
      }

      $('[data-drupal-selector="edit-visibility-ipaddress"]').drupalSetSummary((context) => {
        const $pages = $(context).find('textarea[name="visibility[ipaddress][ipaddress]"]');
        if (!$pages.val()) {
          return Drupal.t('Not restricted');
        }

        return Drupal.t('Restricted to certain IP Address');
      });
    },
  };
}(jQuery, window, Drupal));

