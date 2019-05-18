/**
 * @file
 * Handling Javascript for model popup.
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.bootstrap_login_authenticate = {
    attach: function (context, settings) {
      // Hide default link of forgot password.
      $('.login-forgot').hide();

      // Drupal behaviours are attached again on AJAX calls. The following
      // piece of code is there to make sure we do the job only once per page.
      if (context !== document) {
        return;
      }

      // Submit handler for getting form id.
      $('form').submit(function () {
        form_id = $(this).attr('id');
        $.cookie('form_id', form_id);
      });

      // Open user login Popup when window load.
      $(window).load(function () {
        var form_id = $.cookie('form_id');
        if (form_id === 'user-register-form') {
          $('#create-account').modal('show');
          $('#login-modal').modal('hide');
          $('#forgot_pass').modal('hide');
        }
        else if (form_id === 'user-login') {
          $('#create-account').modal('hide');
          $('#login-modal').modal('show');
          $('#forgot_pass').modal('hide');
        }
        else if (form_id === 'user-pass') {
          $('#create-account').modal('hide');
          $('#login-modal').modal('hide');
          $('#forgot_pass').modal('show');
        }
        else {
          $('#create-account').modal('hide');
          $('#login-modal').modal('show');
          $('#forgot_pass').modal('hide');
        }
        // Show error message.
        function show_error() {
          $('.error-message').append($('.alert-danger').append());
        }
        window.setTimeout(show_error, 100);
        $('.close').hide();
        $.removeCookie('form_id');
      });

      // Make window cannot be destroy any keyword action.
      $('#login-modal').modal({
        backdrop: 'static',
        keyboard: false
      });

      $('#create-account').modal({
        backdrop: 'static',
        keyboard: false
      });

      $('#forgot_pass').modal({
        backdrop: 'static',
        keyboard: false
      });

      // Hide login form and show register form.
      $('#loginRegister').click(function () {
        $('#login-modal').modal().hide();
      });

      // Hide login form and show forgot password form.
      $('#forgotPass').click(function () {
        $('#login-modal').modal().hide();
      });

      // Close button for register and forgot pass, so that login form
      // will be shown.
      $('.modal-footer #close-button').click(function () {
        $('#login-modal').modal().show();
        $('#login-modal', context).appendTo('body');
      });
    }
  };
})(jQuery, Drupal);
