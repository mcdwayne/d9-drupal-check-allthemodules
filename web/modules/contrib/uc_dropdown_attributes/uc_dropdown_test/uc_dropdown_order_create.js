/**
 * @file
 * Javascript behaviors for Dropdown test.
 */

(function ($) {
  'use strict';
  Drupal.behaviors.ucDropdownTest = {
    attach: function (context) {
      $('#edit-customer-type-none').prop('checked', true);
      $('#edit-submit').click();
    }
  };

})(jQuery);
