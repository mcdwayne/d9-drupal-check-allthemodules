/**
 * Provides a tooltip for the field formatter, "Show tooltip on hover".
 */
(function ($) {
  'use strict';
  Drupal.behaviors.field_formatters = {
    attach: function () {
      $('.tooltiphover[title]').qtip();
    }
  };
})(jQuery);



