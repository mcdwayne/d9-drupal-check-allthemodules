(function ($) {
  'use strict';
  Drupal.behaviors.openid_logout = {
    attach: function (context, settings) {
      $('#edit-ji-quickbooks-config-disconnect').click(function () {
        intuit.ipp.anywhere.logout(function () {});
      });
    }
  };
}(jQuery));
