(function ($, Drupal, drupalSettings) {
  'use strict';
	// This function is strict.
  Drupal.behaviors.password_encrypt = {
    attach: function (context, settings) {
      var passkey = drupalSettings.password_encrypt.passkey;
      var cipher;
      var pass;
      var cpass;

      $('#user-login, #user-login-form').submit(function (event) {
        pass = $('#edit-pass').val();
        if (pass !== '') {
          cipher = CryptoJS.AES.encrypt(pass, passkey);
          $('#edit-pass').val(cipher);
        }
      });

      $('#user-register-form, #user-form').submit(function (event) {
        pass = $('#edit-pass-pass1').val();
        cpass = $('#edit-pass-pass2').val();

        if (pass !== cpass) {
          $('span.error').append("<div>Password doesn't match. Please enter correct password.<div>");
          $('#edit-pass-pass2').addClass('error').focus();
          return false;
        }

        if (pass !== '') {
          cipher = CryptoJS.AES.encrypt(pass, passkey);
          $('#edit-pass-pass1').val(cipher);
          $('#edit-pass-pass2').val(cipher);
        }
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
