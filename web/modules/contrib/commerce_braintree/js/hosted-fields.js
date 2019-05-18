/**
 * @file
 * Defines behaviors for the Braintree hosted fields payment method form.
 */

(function ($, Drupal, drupalSettings, braintree) {

  'use strict';

  Drupal.commerceBraintreeHostedFields = function ($form, settings) {
    var $submit = $form.find(':input.button--primary');
    var that = this;

    braintree.client.create({
      authorization: settings.clientToken
    }, function (clientError, clientInstance) {
      if (clientError) {
        console.error(clientError);
        return;
      }

      braintree.hostedFields.create({
        client: clientInstance,
        fields: settings.hostedFields
      }, function (hostedFieldsError, hostedFieldsInstance) {
        that.integration = hostedFieldsInstance;
        if (hostedFieldsError) {
          console.error(hostedFieldsError);
          return;
        }

        $submit.prop('disabled', false);

        $form.on('submit.braintreeSubmit', function (event, options) {
          options = options || {};
          if (options.tokenized) {
            // Tokenization complete, allow the form to submit.
            return;
          }

          event.preventDefault();
          $('.messages--error', $form).remove();

          hostedFieldsInstance.tokenize(function (tokenizeError, payload) {
            if (tokenizeError) {
              console.log(tokenizeError);
              var message = that.errorMsg(tokenizeError);
              // Show the message above the form.
              $form.prepend(Drupal.theme('commerceBraintreeError', message));
              return;
            }

            $('.braintree-nonce', $form).val(payload.nonce);
            $('.braintree-card-type', $form).val(payload.details.cardType);
            $('.braintree-last2', $form).val(payload.details.lastTwo);
            $form.trigger('submit', { 'tokenized' : true });
          });
        });
      });
    });

    return this;
  };

  Drupal.commerceBraintreeHostedFields.prototype.errorMsg = function (tokenizeError) {
    var message;

    switch (tokenizeError.code) {
      case 'HOSTED_FIELDS_FIELDS_EMPTY':
        message = Drupal.t('Please enter your credit card details.');
        break;

      case 'HOSTED_FIELDS_FIELDS_INVALID':
        var fieldName = '';
        var fields = tokenizeError.details.invalidFieldKeys;
        if (fields.length > 0) {
          if (fields.length > 1) {
            var last = fields.pop();
            fieldName = fields.join(', ');
            fieldName += ' and ' + Drupal.t(last);
            message = Drupal.t('The @fields you entered are invalid.', {'@fields': fieldName});
          }
          else {
            fieldName = fields.pop();
            message = Drupal.t('The @field you entered is invalid.', {'@field': fieldName});
          }
        }
        else {
          message = Drupal.t('The payment details you entered are invalid.');
        }

        message += ' ' + Drupal.t('Please check your details and try again.');
        break;

      case 'HOSTED_FIELDS_TOKENIZATION_CVV_VERIFICATION_FAILED':
        message = Drupal.t('The CVV you entered is invalid.');
        message += ' ' + Drupal.t('Please check your details and try again.');
        break;

      case 'HOSTED_FIELDS_FAILED_TOKENIZATION':
        message = Drupal.t('An error occurred while contacting the payment gateway.');
        message += ' ' + Drupal.t('Please check your details and try again.');
        break;

      case 'HOSTED_FIELDS_TOKENIZATION_NETWORK_ERROR':
        message = Drupal.t('Could not connect to the payment gateway.');
        break;

      default:
        message = tokenizeError.message;
    }

    return message;
  };

})(jQuery, Drupal, drupalSettings, window.braintree);
