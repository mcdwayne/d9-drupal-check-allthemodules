/**
 * @file
 * Defines behaviors for the Omise payment method form..
 */

(function ($, Drupal, drupalSettings, omise) {

  'use strict';

  /**
   * Attaches the commerceOmiseForm behavior.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the commerceomiseForm behavior.
   */
  Drupal.behaviors.commerceOmiseForm = {
    attach: function (context) {
      if (!drupalSettings.commerceOmise || !drupalSettings.commerceOmise.publicKey) {
        return;
      }
      $('.omise-form', context).once('omise-processed').each(function () {
        var $form = $('.omise-form', context).closest('form');
        // Clear the token every time the payment form is loaded. We only need the token
        // one time, as it is submitted to Omise after a card is validated. If this
        // form reloads it's due to an error; received tokens are stored in the checkout pane.
        $('#omise_token').val('');
        omise.setPublicKey(drupalSettings.commerceOmise.publicKey);
        var omiseResponseHandler = function (status, response) {
          if (status === 200) {
            // Token contains id, last4, and card type.
            var token = response.id;
            // Insert the token into the form so it gets submitted to the server.
            $('#omise_token').val(token);
            // Do not send card details to server
            $('.card-number').removeAttr('name');
            $('.card-expiry-month').removeAttr('name');
            $('.card-expiry-year').removeAttr('name');
            $('.card-cvc').removeAttr('name');
            // Submit.
            $form.get(0).submit();
          }
          else {
            // Show the errors on the form
            $form.find('.payment-errors').text(response.message);
            $form.find('button').prop('disabled', false);
          }
        };

        $form.submit(function (e) {
          var $form = $(this);
          var card_number = $('.card-number').val();
          var card_expiry_month = $('.card-expiry-month').val();
          var card_expiry_year = $('.card-expiry-year').val();
          var card_cvc = $('.card-cvc').val();
          var card_name = $('.given-name').val() + ' ' + $('.family-name').val();
          var cardObject = {
            name: card_name,
            number: card_number,
            expiration_month: card_expiry_month,
            expiration_year: card_expiry_year,
            security_code: card_cvc
          };
          // Disable the submit button to prevent repeated clicks
          $form.find('button').prop('disabled', true);

          omise.createToken('card', cardObject, omiseResponseHandler);

          // Prevent the form from submitting with the default action.
          if ($('.card-number').length) {
            return false;
          }
        });
      });
    }
  };

  $.extend(Drupal.theme, /** @lends Drupal.theme */{
    commerceomiseError: function (message) {
      return $('<div class="messages messages--error"></div>').html(message);
    }
  });

})(jQuery, Drupal, drupalSettings, window.Omise);
