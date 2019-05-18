/**
 * @file
 * Attaches behaviors for the Timezone condition.
 */
(function ($) {

  "use strict";

  /**
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.timezoneSettingsSummaries = {
    attach: function () {
      // The drupalSetSummary method required for this behavior is not available
      // on the Blocks administration page, so we need to make sure this
      // behavior is processed only if drupalSetSummary is defined.
      if (typeof jQuery.fn.drupalSetSummary === 'undefined') {
        return;
      }

      // There may be an easier way to do this. Right now, we just copy code
      // from block module.
      function selectSummary(context) {
        var vals = [];
        var $select = $(context).find("#edit-visibility-timezone-timezone :selected").text();
        var $negate = $(context).find('input[type="checkbox"]:checked');
        if ($select.length > 0) {
          if (!$negate.val()) {
            vals.push(Drupal.t('Selected timezones'));
          }
          else {
            vals.push(Drupal.t('Not selected timezones'));
          }
        }
        else {
          vals.push(Drupal.t('Not restricted'));
        }
        return vals.join(', ');
      }

      $('[data-drupal-selector="edit-visibility-timezone"]').drupalSetSummary(selectSummary);

    }
  };

})(jQuery);
