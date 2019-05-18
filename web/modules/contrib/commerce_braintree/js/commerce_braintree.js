/**
 * @file
 * Defines behaviors for the Braintree payment method form.
 */

(function ($, Drupal, drupalSettings, braintree) {

  'use strict';

  /**
   * Attaches the commerceBraintreeForm behavior.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the commerceBraintreeForm behavior.
   *
   * @see Drupal.commerceBraintree
   */
  Drupal.behaviors.commerceBraintreeForm = {
    attach: function (context) {
      var $form = $('.braintree-form', context).closest('form').once('braintree-attach');
      if ($form.length === 0) {
        return;
      }

      var waitForSdk = setInterval(function () {
        if (typeof braintree !== 'undefined') {
          var commerceBraintree = {};
          if (drupalSettings.commerceBraintree.integration == 'custom') {
            commerceBraintree = new Drupal.commerceBraintreeHostedFields($form, drupalSettings.commerceBraintree);
          }
          else {
            commerceBraintree = new Drupal.commerceBraintreePaypal($form, drupalSettings.commerceBraintree);
          }
          $form.data('braintree', commerceBraintree);
          clearInterval(waitForSdk);
        }
      }, 100);
    },
    detach: function (context, settings, trigger) {
      // Detaching on the wrong trigger will clear the Braintree form
      // on #ajax (after changing the address country, for example).
      if (trigger !== 'unload') {
        return;
      }
      var $form = $('.braintree-form', context).closest('form');
      if ($form.length === 0) {
        return;
      }

      var commerceBraintree = $form.data('braintree');
      // paypalCheckout doesn't have teardown() method.
      // See https://braintree.github.io/braintree-web/3.19.1/HostedFields.html
      // and https://braintree.github.io/braintree-web/3.19.1/PayPalCheckout.html
      if (commerceBraintree.integration.hasOwnProperty('teardown')) {
        commerceBraintree.integration.teardown();
      }
      $form.removeData('braintree');
      $form.removeOnce('braintree-attach');
      $form.off('submit.braintreeSubmit');
    }
  };

  $.extend(Drupal.theme, /** @lends Drupal.theme */{
    commerceBraintreeError: function (message) {
      return $('<div role="alert">' +
        '<div class="messages messages--error">' + message + '</div>' +
        '</div>'
      );
    }
  });

})(jQuery, Drupal, drupalSettings, window.braintree);
