(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.EidAuthOptionLogin = {
    attach: function () {

      var $mobile_id = $('#mobile-id-login-option');
      var $smart_id = $('#smart-id-login-option');

      // Show fields when errors have occurred.
      if ($mobile_id.find('input').hasClass('error')) {
        $mobile_id.show();
      }

      if ($smart_id.find('input').hasClass('error')) {
        $smart_id.show();
      }

      // Show-hide mobile-id login fields.
      $('.btn-mobile-id').once().on('click', function (e) {
        e.preventDefault();
        $smart_id.hide();
        $mobile_id.slideToggle();

        return false;
      });

      // Show-hide smart-id login fields.
      $('.btn-smart-id').once().on('click', function (e) {
        e.preventDefault();
        $mobile_id.hide();
        $smart_id.slideToggle();

        return false;
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
