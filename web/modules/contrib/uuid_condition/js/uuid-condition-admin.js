/**
 * @file
 * Block config behaviors.
 */

(function ($, window) {

  'use strict';

  /**
   * Provide the summary information for the block settings vertical tabs.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the block settings summaries.
   */
  Drupal.behaviors.blockUuidConditionSettingsSummary = {
    attach: function () {
      // The drupalSetSummary method required for this behavior is not available
      // on the Blocks administration page, so we need to make sure this
      // behavior is processed only if drupalSetSummary is defined.
      if (typeof jQuery.fn.drupalSetSummary === 'undefined') {
        return;
      }

      $('[data-drupal-selector="edit-visibility-uuid"]').drupalSetSummary(function (context) {
        var $uuid_config = $(context).find('textarea[name="visibility[uuid][uuid]"]');
        if (!$uuid_config.val()) {
          return Drupal.t('Not restricted');
        }
        else {
          return Drupal.t('Restricted to certain content');
        }
      });
    }
  };

})(jQuery, window);
