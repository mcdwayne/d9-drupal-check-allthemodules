/**
 * @file
 * Integration file for form_styler module.
 */
(function ($) {

  'use strict';

  Drupal.behaviors.formStyler = {
    attach: function (context, settings) {
      // Check saved options.
      var options;
      if (settings.hasOwnProperty('form_styler_settings')) {
        if (settings.form_styler_settings.options !== undefined) {
          options = settings.form_styler_settings.options;
        } else {
          options = {};
        }
      }
      // initialize form_styler plugin on forms
      // which marked by class '.form-styler-ready'
      $('.form-styler-ready').each(function (i, d) {
        // Exclude form styler on bef select as link fields
        $('.bef-select-as-links > select', d).addClass('without-styler');

        $('input, select', d).styler(options);
      });
    }
  };
})(jQuery);