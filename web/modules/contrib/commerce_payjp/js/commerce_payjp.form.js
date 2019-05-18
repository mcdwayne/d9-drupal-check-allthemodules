/**
 * @file
 * Defines behaviors for the Pay.JP payment method form..
 */

(function ($, Drupal, drupalSettings, payjp) {

  'use strict';

  /**
   * Attaches the commercePayjpForm behavior.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the commercePayjpForm behavior.
   */
  Drupal.behaviors.commercePayjpForm = {
    attach: function (context) {
      var $form = $('.payjp-form', context).closest('form');

      if (drupalSettings.commercePayjp && drupalSettings.commercePayjp.publicKey && !$form.hasClass('payjp-processed')) {
        $form.addClass('payjp-processed');

        // Clear the token every time the payment form is loaded. We only need the token
        // one time, as it is submitted to Pay.JP after a card is validated. If this
        // form reloads it's due to an error; received tokens are stored in the checkout pane.
        $('#payjp_token').val('');

        payjp.setPublicKey(drupalSettings.commercePayjp.publicKey);

        var payjpResponseHandler = function (status, response) {
          if (response.error) {
            // Show the errors on the form
            $form.find('.payment-errors').text(response.error.message);
            $form.find('button').prop('disabled', false);
          }
          else {
            // Token contains id, last4, and card type.
            var token = response.id;
            // Insert the token into the form so it gets submitted to the server.
            $('#payjp_token').val(token);
            // Do not send card details to server
            $('.card-number').removeAttr('name');
            $('.card-expiry-month').removeAttr('name');
            $('.card-expiry-year').removeAttr('name');
            $('.card-cvc').removeAttr('name');
            // Submit.
            $form.get(0).submit();
          }
        };

        $form.submit(function (e) {
          var $form = $(this);
          // Disable the submit button to prevent repeated clicks
          $form.find('button').prop('disabled', false);

          // Validate card form data.
          var card_number = $('.card-number').val();
          var card_expiry_month = $('.card-expiry-month').val();
          var card_expiry_year = $('.card-expiry-year').val();
          var card_cvc = $('.card-cvc').val();

          var validated = true;
          var error_messages = [];

          if (!payjp.validate.cardNumber(card_number)) {
            validated = false;
            error_messages.push(Drupal.t('The card number is invalid.'));
            $('.card-number').addClass('error');
          }
          else {
            $('.card-number').removeClass('error');
          }
          if (!payjp.validate.expiry(card_expiry_month, card_expiry_year)) {
            validated = false;
            error_messages.push(Drupal.t('The expiry date is invalid.'));
            $('.card-expiry-month').addClass('error');
            $('.card-expiry-year').addClass('error');
          }
          else {
            $('.card-expiry-month').removeClass('error');
            $('.card-expiry-year').removeClass('error');
          }
          if (!payjp.validate.cvc(card_cvc)) {
            validated = false;
            error_messages.push(Drupal.t('The verification code is invalid.'));
            $('.card-cvc').addClass('error');
          }
          else {
            $('.card-cvc').removeClass('error');
          }
          if (error_messages.length > 0) {
            var payment_errors = '<ul>';
            error_messages.forEach(function (error_message) {
              payment_errors += '<li>' + error_message + '</li>';
            });
            payment_errors += '</ul>';
            $form.find('.payment-errors').html(Drupal.theme('commercePayjpError', payment_errors));
          }

          // Create token if the card form was validated.
          if (validated) {
            payjp.createToken({
              number: card_number,
              exp_month: card_expiry_month,
              exp_year: card_expiry_year,
              cvc: card_cvc
            }, payjpResponseHandler);
          }

          // Prevent the form from submitting with the default action.
          if ($('.card-number').length) {
            return false;
          }
        });
      }
    }
  };

  $.extend(Drupal.theme, /** @lends Drupal.theme */{
    commercePayjpError: function (message) {
      return $('<div class="messages messages--error"></div>').html(message);
    }
  });

})(jQuery, Drupal, drupalSettings, window.Payjp);
