/**
 * @file
 * Behaviors for "password_toggle" module.
 */

(function ($, Drupal, drupalSettings) {

  /**
   * Add a "Show password" checkbox to each password field.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches "Show password" checkbox to password fields.
   */
  Drupal.behaviors.showPassword = {
    attach: function (context) {
      // Create the checkbox.
      var showPassword = $('<label class="password-toggle"><input type="checkbox" />' + Drupal.t('Show password') + '</label>');
      // Add click handler to checkboxes.
      $(':checkbox', showPassword).click(function () {
        $password_field = $(this).closest('.form-type-password').find(':password');
        if ($password_field.length === 0) {
          return;
        }
        $preview = $(this).closest('.form-type-password').find('.password-preview');
        if ($preview.length === 0) {
          $preview = $('<div>')
                  .addClass('password-preview')
                  .insertAfter($password_field);
        }
        if ($(this).is(':checked')) {
          // Fill and show the password preview.
          $preview.text($password_field.val());
          $preview.slideDown();
        }
        else {
          // Hide password preview.
          $preview.slideUp();
          $preview.text('');
        }
      });

      // Update password preview.
      var updatePasswordPreview = function () {
        $preview = $(this).closest('.form-type-password').find('.password-preview');
        if ($preview.is(':visible')) {
          $preview.text($(this).val());
        }
      }

      var $passwordInput = $(context).find(':password');
      $passwordInput.on('input', updatePasswordPreview);

      // Add checkbox to all password field on the current page.
      showPassword.insertAfter($passwordInput);
    }
  };

})(jQuery, Drupal, drupalSettings);
