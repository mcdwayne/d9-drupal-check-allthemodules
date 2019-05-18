/**
 * @file
 * CKEditor 'sitesearch' plugin admin behavior.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';
  /**
   * Provides the summary for the "sitesearch" plugin settings vertical tab.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behaviour to the "sitesearch" settings vertical tab.
   */

  Drupal.behaviors.ckeditorSiteSearchSettingsSummary = {
    attach: function () {
      $('[data-ckeditor-plugin-id="sitesearch"]').drupalSetSummary(function (context) {
        var $enable = $('input[name="editor[settings][plugins][sitesearch][enable]"]');
        var $search_path = $('input[name="editor[settings][plugins][sitesearch][search_path]"]');

        if (!$enable.is(':checked')) {
          return Drupal.t('Syntax highlighting <strong>disabled</strong>.');
        }

        var output = '';
        output += Drupal.t('Syntax highlighting <strong>enabled</strong>.');
        output += '<br />' + Drupal.t('Search Path: ') + $search_path.val();
        return output;
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
