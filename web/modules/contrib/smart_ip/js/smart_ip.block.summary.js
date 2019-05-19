/**
 * @file
 * Block behaviors.
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
  Drupal.behaviors.smartIpBlockSettingsSummary = {
    attach: function (context) {

      /**
       * Create a summary for 'select' in the provided context.
       *
       * @param {HTMLDocument|HTMLElement} context
       *   A context where one would find 'select' to summarize.
       *
       * @return {string}
       *   A string with the summary.
       */
      function selectElementSummary(context) {
        var vals = [];
        var $options = $(context).find('select option:selected');
        var il = $options.length;
        for (var i = 0; i < il; i++) {
          vals.push($($options[i]).html());
        }
        if (!vals.length) {
          vals.push(Drupal.t('Not restricted'));
        }
        return vals.join(', ');
      }
      $('[data-drupal-selector="edit-visibility-countries"]').drupalSetSummary(selectElementSummary);
    }
  };
})(jQuery, window, Drupal);