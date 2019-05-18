/**
 * @file
 * Javascript behaviors for Dropdown test.
 */

(function ($) {
  'use strict';
  Drupal.behaviors.ucDropdownTest = {
    attach: function (context) {
      $('input#edit-product').click(function () {
        var user = $('input[name="user"]:checked').val();
        var type = $('input[name="type"]:checked').val();
        $.get('/uc_dropdown_test/product/' + user + '/' + type, null,
          ucDropdownTest);
        return false;
      });
      $('input#edit-class').click(function () {
        var user = $('input[name="user"]:checked').val();
        var type = $('input[name="type"]:checked').val();
        $.get('/uc_dropdown_test/product_class/' + user + '/' + type, null,
          ucDropdownTest);
        return false;
      });
      $('input#edit-kit').click(function () {
        var user = $('input[name="user"]:checked').val();
        var type = $('input[name="type"]:checked').val();
        $.get('/uc_dropdown_test/product_kit/' + user + '/' + type, null,
          ucDropdownTest);
        return false;
      });
    }
  };

  var ucDropdownTest = function (response) {
    if (response.status) {
      if (response.user === 'customer') {
        location.href = '/node/' + response.nid;
      }
      else {
        location.href = '/admin/store/orders/create/';
      }
    }
  };

})(jQuery);
