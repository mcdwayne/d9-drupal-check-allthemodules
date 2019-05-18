(function ($, Drupal, settings) {

  var donationForm = new Drupal.druminateCore({
    api: 'donation'
  });

  /**
   * Helper function used to grab Luminate Api Settings.
   *
   * @returns {{}}
   */
  donationForm.getSettings = function () {
    if (settings.druminateWebforms &&
        settings.druminateWebforms.donationFormSettings) {
      return settings.druminateWebforms.donationFormSettings;
    }
    return {};
  };

  /**
   * Helper function used to grab parameters from query string.
   *
   * @param name
   * @returns {string}
   */
  donationForm.getUrlParameter = function (name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    var results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
  };

  /**
   * Helper function used to map submissions from the Drupal Webform to create
   * an object of parameters to be sent to Luminate.
   *
   * @param _form
   * @param formSettings
   * @returns {*}
   */
  donationForm.getSubmissionData = function (_form, formSettings) {
    // Use the mapping from the Drupal Webform to create an object of
    // parameters to be sent to Luminate.
    var _self = _form;
    var rawFormValues = _self.serializeArray();
    var formFields = {};
    Object.keys(rawFormValues).forEach(function (key) {
      formFields[rawFormValues[key].name] = rawFormValues[key].value;
    });

    var fieldMapping = formSettings.convio_mapping;
    var luminateParams = {
      form_id: formSettings.df_id,
      validate: 'true'
    };

    Object.keys(fieldMapping).forEach(function (key) {
      if (fieldMapping[key]) {
        var luminateKey = key.replace(/-/g, '.');
        var mapValue = fieldMapping[key];
        // Check to see if key contains a ":" and format accordingly.
        if (mapValue.indexOf(':') !== -1) {
          mapValue = mapValue.replace(':', '[') + ']';
        }

        if (formFields[mapValue] && formFields[mapValue].length > 0) {
          luminateParams[luminateKey] = formFields[mapValue];
        }
      }
    });

    // Submit the Donation Form in Test mode.
    if (settings.druminate &&
      settings.druminate.settings &&
      settings.druminate.settings.test_mode &&
      settings.druminate.settings.test_mode === 1) {
      luminateParams.df_preview = true;
    }

    // Swap method based on exproc.
    if (luminateParams.extproc === 'credit') {
      luminateParams.method = 'donate';
    }
    else {
      // Amazon and Paypal payments need to be made via the startDonation
      // method and redirected to the proper place.
      luminateParams.method = 'startDonation';
    }

    // If level_autorepeat is false remove it from the request all together
    // since the api does not check the value of the param just the presence.
    if (luminateParams.level_autorepeat === 'false') {
      delete luminateParams.level_autorepeat;
    }

    // Add source and sub_source if present in url.
    if (this.getUrlParameter('source')) {
      luminateParams.source = this.getUrlParameter('source');
    }
    if (this.getUrlParameter('sub_source')) {
      luminateParams.sub_source = this.getUrlParameter('sub_source');
    }

    return $.param(luminateParams);
  };

  /**
   * Success callback for API submission.
   *
   * @param data
   */
  donationForm.success = function (data) {
    if (data.donationResponse) {
      // Remove all error divs and start fresh each time.
      $form.find('.messages.donation-form--error').remove();
      // Redirect for ext proc
      if (data.donationResponse.redirect && data.donationResponse.redirect.url) {
        window.location.href = data.donationResponse.redirect.url;
      }
      else {
        // Scroll to the top of the form to display error messages.
        $('html, body').animate({
          scrollTop: $form.offset().top
        }, 500, 'linear');

        // Display error messages in custom div.
        if (data.donationResponse.errors) {
          console.log(data);
          $form.prepend('<div role="contentinfo" aria-label="Error message" class="messages messages--error donation-form--error"><ul></ul></div>');

          if (data.donationResponse.errors.pageError) {
            $form.find('.messages.donation-form--error ul').append('<li>' + data.donationResponse.errors.pageError + '</li>');
          }

          var errors = data.donationResponse.errors.fieldError;
          if (Array.isArray(errors)) {
            errors.forEach(function (error) {
              $form.find('.messages.donation-form--error ul').append('<li>' + error + '</li>');
            });
          }
          else {
            $('.messages.donation-form--error ul').append('<li>' + errors + '</li>');
          }
        }

        // Display confirmation message or redirect.
        if (data.donationResponse.donation) {
          if (settings.druminateWebforms &&
            settings.druminateWebforms.donationFormConfirmation) {
            var confSettings = settings.druminateWebforms.donationFormConfirmation;
            if (confSettings.confirmation_type === 'url' || confSettings.confirmation_type === 'url_message') {
              window.location.href = confSettings.confirmation_url;
            }
            else {
              var confMessage = '';
              if (confSettings.confirmation_title) {
                confMessage += '<strong>' + confSettings.confirmation_title + '</strong>';
              }
              if (confSettings.confirmation_message) {
                confMessage += confSettings.confirmation_message;
              }

              $form.once('donationFormsSuccess').parent().prepend('<div role="contentinfo" aria-label="Status message" class="messages messages--status donation-form--status"><ul><li>' + confMessage + '</li></ul></div>');
              $form.hide();
            }
          }
        }
      }
    }
  };

  donationForm.error = function (data) {
    console.log(data);
  };

  var formSettings = donationForm.getSettings();
  var $form = $('#' + formSettings.id);

  Drupal.behaviors.donationForms = {
    attach: function (context) {
      // Prevent event submission and submit the form with JS.
      $form.submit(function (e) {
        e.preventDefault();
        var data = donationForm.getSubmissionData($form, formSettings);
        donationForm.request({
          data: data
        });
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
