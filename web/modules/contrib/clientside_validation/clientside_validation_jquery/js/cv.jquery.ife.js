/**
 * @file
 * Attaches behaviors for the Clientside Validation jQuery module.
 */
(function ($) {
  // Override clientside validation jquery validation options.
  // We do this to display the error markup same as in inline_form_errors.
  $(document).once('cvjquery').on('cv-jquery-validate-options-update', function (event, options) {
    options.errorElement = 'strong';
    options.showErrors = function(errorMap, errorList) {
      // First remove all errors.
      for (var i in errorList) {
        $(errorList[i].element).parent().find('.form-item--error-message').remove();
      }

      // Show errors using defaultShowErrors().
      this.defaultShowErrors();

      // Wrap all errors with div.form-item--error-message.
      $(this.currentForm).find('strong.error').each(function () {
        if (!$(this).parent().hasClass('form-item--error-message')) {
          $(this).wrap('<div class="form-item--error-message"/>');
        }
      });
    };
  });
})(jQuery);
