/**
 * @file
 * jQuery to provide summary information inside vertical tabs.
 */

(function ($) {

  'use strict';

  /**
   * Provide summary information for vertical tabs.
   */
  Drupal.behaviors.parselyTabs = {
    attach: function (context) {

      // Provide summary during content type configuration.
      $('#edit-parsely-tag', context).drupalSetSummary(function (context) {
        var summary = '';
        if ($('#edit-parsely-tag-enable', context).is(':checked')) {
          summary = Drupal.t('Parse.ly tag <strong>enabled</strong>.');
        }
        else {
          summary = Drupal.t('Parse.ly tag disabled.');
        }
        return summary;
      });
    }
  };

})(jQuery);
