/**
 * @file
 * Packages block admin behaviors.
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
  Drupal.behaviors.packagesBlockSettingsSummary = {
    attach: function () {
      // The drupalSetSummary method required for this behavior is not available
      // on the Blocks administration page, so we need to make sure this
      // behavior is processed only if drupalSetSummary is defined.
      if (typeof $.fn.drupalSetSummary === 'undefined') {
        return;
      }

      /**
       * Create a summary for selected in the provided context.
       *
       * @param {HTMLDocument|HTMLElement} context
       *   A context where one would find selected to summarize.
       *
       * @return {string}
       *   A string with the summary.
       */
      function selectSummary(context) {
        var selected = $(context).find('#edit-visibility-package-package option:selected').map(function () { return this.value !== 0 ? this.text : null; }).get().join();
        return selected ? selected : Drupal.t('Not restricted');
      }

      $('[data-drupal-selector="edit-visibility-package"]').drupalSetSummary(selectSummary);
    }
  };

})(jQuery, window, Drupal);
