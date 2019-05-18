/**
 * @file
 * Attaches behaviors for the Date condition.
 */
(function ($) {

  "use strict";

  /**
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.dateSettingsSummaries = {
    attach: function () {
      // The drupalSetSummary method required for this behavior is not available
      // on the Blocks administration page, so we need to make sure this
      // behavior is processed only if drupalSetSummary is defined.
      if (typeof jQuery.fn.drupalSetSummary === 'undefined') {
        return;
      }
      // There may be an easier way to do this. Right now, we just copy code
      // from block module.
      function textfieldSummary(context) {
        var vals = [];
        var $textfield = $(context).find('input[name="visibility[date][date]"]');
        var $negate = $(context).find('input[type="checkbox"]:checked');
        if (!$textfield.val()) {
          return Drupal.t('Not restricted');
        }
        else {
          return Drupal.t('On or after ' + $textfield.val());
        }
      }

      function textfield2Summary(context) {
        var vals = [];
        var $textfield = $(context).find('input[name="visibility[date_before][date_before]"]');
        var $negate = $(context).find('input[type="checkbox"]:checked');
        if (!$textfield.val()) {
          return Drupal.t('Not restricted');
        }
        else {
          return Drupal.t('Before ' + $textfield.val());
        }
      }

      $('[data-drupal-selector="edit-visibility-date"]').drupalSetSummary(textfieldSummary);
      $('[data-drupal-selector="edit-visibility-date-before"]').drupalSetSummary(textfield2Summary);

    }
  };

})(jQuery);
