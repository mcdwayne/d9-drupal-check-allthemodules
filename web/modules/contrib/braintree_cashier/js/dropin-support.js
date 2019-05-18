/**
 * @file
 * Supports the signup form created with Braintree's Drop-in UI.
 */
(function ($, Drupal, drupalSettings) {

  'use strict';

  var dropinInstance;
  var buttonInitial;
  var buttonFinal;
  var nonceField;

  /**
   * Callback for the click event on the visible submit button.
   *
   * @param {jQuery.Event} event
   */
  function onInitialButtonClick(event) {
    event.preventDefault();

    buttonInitial.prop('disabled', true)
      .addClass('is-disabled');

    if (!dropinInstance) {
      console.log('Drop-in instance is undefined.');
      enableButtonInitial();
      return;
    }

    dropinInstance.requestPaymentMethod(function (requestPaymentMethodErr, payload) {
      if (requestPaymentMethodErr) {
        console.log(requestPaymentMethodErr);
        enableButtonInitial();
        return;
      }
      nonceField.val(payload.nonce);
      buttonFinal.click();
    });

    // stopImmediatePropagation since this event handler was getting submitted
    // multiple times during automated tests.
    event.stopImmediatePropagation();

  }

  /**
   * Enable the visible submit button and attach the click handler.
   */
  function enableButtonInitial() {
    buttonInitial.prop('disabled', false)
      .removeClass('is-disabled')
      .one('click', onInitialButtonClick);
  }

  /**
   * Callback for after the Dropin UI instance is created.
   *
   * @param createErr
   *   The error generated if the Dropin UI could not be created.
   * @param {object} instance
   *   The Braintree Dropin UI instance.
   *
   * @see https://braintree.github.io/braintree-web-drop-in/docs/current/Dropin.html
   */
  function onInstanceCreate(createErr, instance) {
    dropinInstance = instance;
    enableButtonInitial();
  }

  /**
   * Create the Braintree Dropin UI.
   *
   * @type {{attach: Drupal.behaviors.signupForm.attach}}
   */
  Drupal.behaviors.signupForm = {
    attach: function (context, settings) {
      $('body', context).once('instantiate-dropin').each(function() {
        
        buttonInitial = $('#submit-button', context);
        buttonFinal = $('#final-submit', context);
        nonceField = $('#payment-method-nonce', context);

        var createParams = {
          authorization: drupalSettings.braintree_cashier.authorization,
          container: '#dropin-container'
        };

        if (drupalSettings.braintree_cashier.acceptPaypal) {
          createParams.paypal = {
            flow: 'vault'
          };
        }

        braintree.dropin.create(createParams, onInstanceCreate);
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
