/**
 * @file
 * Provides overridable theme functions for all of Quick Edit's client-side HTML.
 */

(function($, Drupal) {

  "use strict";

  Drupal.behaviors.dropkick = {
    attach: function(context) {
      $(drupalSettings.dropkick.selector, context)
         // Disable dropkick on field ui.
        .not('#field-ui-field-overview-form select, #field-ui-display-overview-form select')
        .each(function() {
          var $element = $(this);
          $element.dropkick({
            mobile: drupalSettings.dropkick.mobile_support,
            change: function() {
              $element.change();
            },
          });
      });

      // Add clearfix to parent .form-item to fix floats.
      $('.dk_container').parents('.form-item').addClass('clearfix');
    }
  }

})(jQuery, Drupal);
