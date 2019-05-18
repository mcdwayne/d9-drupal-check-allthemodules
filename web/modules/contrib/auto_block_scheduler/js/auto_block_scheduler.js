/**
 * @file
 * JQuery to provide summary information inside vertical tabs.
 */

(function ($) {

  'use strict';

  /**
   * Provide summary information for vertical tabs.
   */
  Drupal.behaviors.auto_block_scheduler_settings = {
    attach: function (context) {

      // The drupalSetSummary method required for this behavior is not available
      // on the Blocks administration page, so we need to make sure this
      // behavior is processed only if drupalSetSummary is defined.
      if (typeof jQuery.fn.drupalSetSummary === 'undefined') {
        return;
      }

      // Provide summary when configure a block.
      $('details#edit-visibility-auto-block-scheduler', context).drupalSetSummary(function (context) {
        var vals = [];
        if ($('#edit-visibility-auto-block-scheduler-published-on-date').val()) {
          vals.push(Drupal.t('Scheduled for publishing'));
        }
        if ($('#edit-visibility-auto-block-scheduler-unpublished-on-date').val()) {
          vals.push(Drupal.t('Scheduled for unpublishing'));
        }
        if (!vals.length) {
          vals.push(Drupal.t('Not scheduled'));
        }
        return vals.join('<br/>');
      });

    }
  };

})(jQuery);
