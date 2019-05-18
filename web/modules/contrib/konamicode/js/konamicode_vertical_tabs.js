/**
 * @file
 * Vertical Tabs javascript for setting a summary on the tab level.
 */

(function ($) {
  'use strict';

  /**
   * Provide summary information for vertical tabs.
   */
  Drupal.behaviors.konamicode_configuration = {
    attach: function (context) {
      let listItems = $(".vertical-tabs__menu .vertical-tabs__menu-item");
      listItems.each(function (index, listItem) {
        let tab = $(listItem);
        // The complete link ID, e.g. #edit-konamicode-action-redirect.
        let tabLinkId = tab.find('a').attr('href');
        // E.g. 'redirect'.
        let actionName = tabLinkId.substring(24);
        $(tabLinkId, context).drupalSetSummary(function (context) {
          let summaries = [];
          if ($('#edit-konamicode-' + actionName + '-enabled', context).is(':checked')) {
            summaries.push(Drupal.t('Enabled'));
          }
          return summaries.join('<br/>');
        });

      });
    }
  };
})(jQuery);
