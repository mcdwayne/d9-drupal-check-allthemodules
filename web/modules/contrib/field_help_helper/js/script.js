/**
 * @file
 * JavaScript file for the Field help helper module.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.field_help_helper = {
    attach: function (context) {
      var $editLinks = $('a[data-drupal-selector="edit-field-help-helper-link"]', context);
      var $helpText;
      var helpTextSelector = '.description';

      $editLinks.each(function () {
        $helpText = $(this).parent().find(helpTextSelector);
        if ($helpText.is(':visible')) {
          $(this)
            .once('field-help-helper-link')
            .appendTo($helpText);
        }
      });
    }
  };

}(jQuery, Drupal));
