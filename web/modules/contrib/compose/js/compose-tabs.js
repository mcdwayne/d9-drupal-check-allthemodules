(function ($, Drupal) {
  Drupal.behaviors.composeTabs = {
    attach: function (context, settings) {

      // Do these once on intial page load
      $('body').once().each(function () {

        // Make tabs
        $('#compose-tabs').tabs().promise().done(function (){
          // Show tabs with errors on page load
          showErrorsOnTabs();
          if ($('#compose-tabs .compose-tabs li.ui-state-error').length > 0) {
            // Load first tab with error
            $('#compose-tabs').tabs('option', 'active', $('#compose-tabs .compose-tabs li.ui-state-error:first').index());
          }
        });

        // Add css identifier to body
        $('body').addClass('compose-edit-mode');

      });

      // Validate on form blur
      $('form[data-compose="compose-form"] .required', context).blur(function (event) {
        composeValidateElement($(this));
      });

      function composeValidateElement(element) {
        var region = $(element).closest('.compose-tab-region').data('region');

        if (element.is('select')) {
          if (element.val() == '_none') {
            element[0].setCustomValidity('Selection must be made');
          }
          else {
            element[0].setCustomValidity('');
          }
        }

        if (element[0].checkValidity()) {
          $(element).removeClass('error');
          $(element).closest('.form-item')
            .removeClass('has-error')
            .removeClass('error');
        }
        else {
          $(element).addClass('error');
          $(element).closest('.form-item')
            .addClass('has-error')
            .addClass('error');
        }

        showErrorsOnTabs();
        setValidationMessage(checkFormErrorsExist());
      }

      function showErrorsOnTabs() {
        $('.compose-tab-region').each(function (){
          var region = $(this).data('region');

          if ($(this).find('.error').length > 0) {
            $('.compose-tab-' + region).addClass('ui-state-error');
          }
          else {
            $('.compose-tab-' + region).removeClass('ui-state-error');
          }
        });
      }

      function checkFormErrorsExist() {
        if ($('.error').length > 0) {
          return false;
        }
        else {
          return true;
        }
      }

      function setValidationMessage(form_valid) {
        if ($('div[data-drupal-messages] .messages__wrapper').children().length == 0) {
          if (form_valid) {
            $('#compose-validation-message', context).text('')
              .removeClass('messages')
              .removeClass('messages--error');
          }
          else {
            $('#compose-validation-message', context)
              .text('There are validation errors on this form. See highlighted tabs below.')
              .addClass('messages')
              .addClass('messages--error');
          }
        }
      }

    }
  };
})(jQuery, Drupal);
