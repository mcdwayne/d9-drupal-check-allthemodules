/**
 * @file
 * Javascript behaviors for the OnePass module.
 */

(function ($) {

  'use strict';

  /**
   * Adds summaries to the OnePass section.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behavior to OnePass section.
   */
  Drupal.behaviors.onepassNodeForm = {
    attach: function (context) {
      $(context).find('#edit-onepass').drupalSetSummary(function (context) {
        if ($('input[type="radio"]:checked', context).val() === '1') {
          return Drupal.t('integration: Enabled');
        }
        else {
          return Drupal.t('integration: Disabled');
        }
      });
    }
  };

})(jQuery);
