/**
 * @file
 * CKEditor 'imagepopup' plugin admin behavior.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Provides the summary for the "imagepopup" plugin settings vertical tab.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behaviour to the "imagepopup" settings vertical tab.
   */
  Drupal.behaviors.ckeditorImagePopupSettingsSummary = {
    attach: function () {
      $('[data-ckeditor-plugin-id="imagepopup"]').drupalSetSummary(function (context) {
        var root = 'input[name="editor[settings][plugins][imagepopup][image_upload]';
        var $status = $(root + '[status]"]');
        var $maxFileSize = $(root + '[max_size]"]');
        var $maxWidth = $(root + '[max_dimensions][width]"]');
        var $maxHeight = $(root + '[max_dimensions][height]"]');
        var $scheme = $(root + '[scheme]"]:checked');

        var maxFileSize = $maxFileSize.val() ? $maxFileSize.val() : $maxFileSize.attr('placeholder');
        var maxDimensions = ($maxWidth.val() && $maxHeight.val()) ? '(' + $maxWidth.val() + 'x' + $maxHeight.val() + ')' : '';

        if (!$status.is(':checked')) {
          return Drupal.t('Uploads disabled');
        }

        var output = '';
        output += Drupal.t('Uploads enabled, max size: @size @dimensions', {'@size': maxFileSize, '@dimensions': maxDimensions});
        if ($scheme.length) {
          output += '<br />' + $scheme.attr('data-label');
        }
        return output;
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
