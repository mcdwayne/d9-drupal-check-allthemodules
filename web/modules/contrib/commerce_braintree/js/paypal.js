/**
 * @file
 * Defines behaviors for the Braintree paypal checkout payment method form.
 */

(function ($, Drupal, drupalSettings, braintree) {

  'use strict';

  Drupal.commerceBraintreePaypal = function ($form, settings) {
    var $submit = $form.find(':input.button--primary');
    var that = this;
    braintree.client.create({
      authorization: settings.clientToken
    }, function (clientError, clientInstance) {
      if (clientError) {
        console.error('Error creating client:', clientError);
        return;
      }
      // Disable the Continue button until we get a nonce.
      $submit.attr("disabled", "disabled");
      braintree.paypalCheckout.create({
        client: clientInstance
      }, function (paypalCheckoutError, paypalCheckoutInstance) {
        that.integration = paypalCheckoutInstance;
        // Stop if there was a problem creating a PayPal Checkout.
        if (paypalCheckoutError) {
          console.error('Error creating PayPal:', paypalCheckoutError);
          return;
        }

        var renderOptions = {
          env: settings.environment,

          payment: function () {
            var options = {
              flow: 'vault'
            };
            if (drupalSettings['commerceBraintree']['paymentMethodType'] == "paypal_credit") {
              options.offerCredit = true;
            }
            return paypalCheckoutInstance.createPayment(options);
          },

          onAuthorize: function (data, actions) {
            return paypalCheckoutInstance.tokenizePayment(data)
              .then(function (payload) {
                // May be there is a better way to display email. In the old
                // system Paypal was doing it automatically.
                $('#paypal-button', $form).append('<div class="paypal-account">' + Drupal.t('PayPal account (') + payload.details.email + ')</div>');

                // Hiding the button now that we have the nonce.
                $('#paypal-button .paypal-button-context-iframe').hide();

                // Submit 'payload.nonce' to the server.
                $('.braintree-nonce', $form).val(payload.nonce);
                // We have a nonce, let's enable the Continue button.
                $submit.prop('disabled', false);
              });
          },

          onCancel: function (data) {
            console.log('Payment cancelled', JSON.stringify(data, 0, 2));
          },

          onError: function (error) {
            console.error(error);
            var message = that.errorMsg(error);
            // Show the message above the form.
            $form.prepend(Drupal.theme('commerceBraintreeError', error));
            return;
          }
        };

        if (drupalSettings['commerceBraintree']['paymentMethodType'] == 'paypal_credit') {
          renderOptions.style = {
            label: 'credit'
          };
        }

        var waitForSdk = setInterval(function () {
          if (typeof paypal !== 'undefined') {
            clearInterval(waitForSdk);
            paypal.Button.render(renderOptions, '#paypal-button');
          }
        }, 100);
      });
    });

    return this;
  };

})(jQuery, Drupal, drupalSettings, window.braintree);
