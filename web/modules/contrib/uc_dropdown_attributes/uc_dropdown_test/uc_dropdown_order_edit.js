/**
 * @file
 * Javascript behaviors for Dropdown test.
 */

(function ($) {
  'use strict';
  Drupal.behaviors.ucDropdownTest = {
    attach: function (context) {
      var control = $('select#edit-product-controls-nid').attr('size');
      if (typeof control == 'undefined') {
        if (typeof $('input#edit-product-controls-actions-submit').val() == 'undefined') {
          $('#edit-add-product-button').click();
          return;
        }
        // Continue with test.
      }
      else {
        var max = 0;
        $('select#edit-product-controls-nid option').each(function () {
          if (parseInt($(this).val()) > max) {
            max = parseInt($(this).val());
          }
        });
        $('div#product-controls select option[value="' + max + '"]').attr('selected', 'selected');
        $('#edit-product-controls-actions-select').click();
        return;
      }
    }
  };
})(jQuery);
