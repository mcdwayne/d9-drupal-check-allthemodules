/**
 * @file
 * Defines behaviors for the Vantiv eProtect payment tokenization feature.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Attaches the vantivCreditCardEprotect behavior.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the vantivCreditCardEprotect behavior.
   *
   * @see Drupal.vantivEprotect.attach
   */
  Drupal.behaviors.vantivCreditCardEprotect = {
    attach: function (context, settings) {
      // Only attach when required elements exist.
      $('#vantivRequestPaypageId').once().each(function () {
        Drupal.vantivEprotect.attach(context, settings);
      });
    },
    detach: function(context, settings, trigger) {
      Drupal.vantivEprotect.detach(context, settings, trigger);
    }
  };

  /**
   * @typedef {object} Drupal~settings~vantivSettings
   *
   * @prop {string} mode
   *   The payment gateway transaction environment, either 'live' or 'prelive'.
   * @prop {boolean} checkout_pane
   *   TRUE if operating on a Checkout 'new payment method' form.
   * @prop {array} parents
   *   An array of #parents from the Drupal form structure.
   */

  /**
   * Namespace for the Vantiv eProtect functionality.
   *
   * @namespace
   */
  Drupal.vantivEprotect = {

    /**
     * API timeout setting (milliseconds).
     */
    timeout: 15000,

    /**
     * Attaches our functionality in the given context.
     *
     * @param {object} context
     *   The context.
     * @param {Drupal.settings} settings
     *   The Drupal settings.
     */
    attach: function (context, settings) {
      if (settings.commerce_vantiv) {
        settings = settings.commerce_vantiv.eprotect;
        Drupal.vantivEprotect.load(settings);
        var buttonId = Drupal.vantivEprotect.getSubmitButtonSelector(settings);
        Drupal.vantivEprotect.delegateSubmitButton(buttonId, settings);
      }
    },

    /**
     * Detaches our functionality from the given context.
     *
     * @param {object} context
     *   The context.
     * @param {Drupal.settings} settings
     *   The Drupal settings.
     * @param trigger
     *   Either 'unload', 'move', or 'serialize'.
     */
    detach: function (context, settings, trigger) {
      if (settings.commerce_vantiv) {
        settings = settings.commerce_vantiv.eprotect;
        var buttonId = Drupal.vantivEprotect.getSubmitButtonSelector(settings);
        Drupal.vantivEprotect.undelegateSubmitButton(buttonId, settings);
      }
    },

    /**
     * Loads the external Litle PayPage (eProtect) library script for the current transaction mode.
     *
     * @param {Drupal.settings.vantivSettings} settings
     */
    load: function (settings) {
      if ((typeof LitlePayPage === 'function')) {
        return;
      }
      $.getScript(Drupal.vantivEprotect.getPayPageHost(settings) + '/eProtect/litle-api2.js');
    },

    /**
     * Delegates the form's submit button click event to Vantiv eProtect.
     *
     * @param {string} submitButtonId
     * @param {Drupal.settings.vantivSettings} settings
     */
    delegateSubmitButton: function (submitButtonId, settings) {
      $(submitButtonId).on('click', {
        settings: settings
      }, Drupal.vantivEprotect.handlePaymentFormSubmitClickEvent);
    },

    /**
     * Undelegates the Vantiv eProtect handler from the form's submit button.
     *
     * @param {string} submitButtonId
     * @param {Drupal.settings.vantivSettings} settings
     */
    undelegateSubmitButton: function (submitButtonId, settings) {
      $(submitButtonId).off('click', Drupal.vantivEprotect.handlePaymentFormSubmitClickEvent);
    },

    /**
     * Handles the checkout form submit event when Vantiv eProtect is in use.
     *
     * @param {object} event
     *   The click event.
     * @param {boolean} passthru
     *   TRUE to use the default form submit handling.
     *
     * @return {boolean}
     *   TRUE if the form should continue with default submit handling.
     */
    handlePaymentFormSubmitClickEvent: function (event, passthru) {

      // Get settings for this closure from the parent scope.
      var settings = event.data.settings;

      // Use the default (Drupal) behaviour if we've already successfully submitted to Vantiv.
      if (passthru) {
        return true;
      }

      var submitButton = event.currentTarget;

      // Clear Litle response fields.
      Drupal.vantivEprotect.setLitleResponseFields({'response': '', 'message': ''});

      // Build the custom success callback.
      var onSuccess = function (response) {

        // Set the transaction/token values from Vantiv in our hidden form fields.
        Drupal.vantivEprotect.setLitleResponseFields(response);

        // Trigger this submit handler again using the passthru flag.
        // @todo: Test expiration date here to avoid trip to Drupal
        // since all other payment fields are handled client-side.
        $(submitButton).trigger('click', true);
      };

      // Build the request based on current form values.
      var request = Drupal.vantivEprotect.getRequest(settings);
      var fields = Drupal.vantivEprotect.getCommerceFormFields(settings);
      var onError = Drupal.vantivEprotect.onErrorAfterLitle;
      var onTimeout = Drupal.vantivEprotect.timeoutOnLitle;
      var timeout = Drupal.vantivEprotect.timeout;

      // Make the API call.
      var api = new LitlePayPage();
      api.sendToLitle(request, fields, onSuccess, onError, onTimeout, timeout);

      // Prevent further regular form submit handling.
      return false;
    },

    /**
     * Gets the PayPage host for the given transaction mode.
     *
     * @param {Drupal.settings.vantivSettings} settings
     *
     * @returns {string} URL of the eProtect host without a trailing slash.
     */
    getPayPageHost: function (settings) {
      if (settings.mode === 'live') {
        return 'https://request.eprotect.vantivcnp.com';
      }
      else if (settings.mode === 'post-live') {
        return 'https://request.eprotect.vantivpostlive.com';
      }

      return 'https://request.eprotect.vantivprelive.com';
    },

    /**
     * Gets the submit button ID based on the current form being used.
     *
     * @param {Drupal.settings.vantivSettings} settings Vantiv settings.
     *
     * @returns {string} jQuery selector for the submit button(s) to control.
     */
    getSubmitButtonSelector: function (settings) {
      return '[data-drupal-selector="edit-actions"] [name="op"]';
    },

    /**
     * Gets the Drupal Commerce form fields that hold the values to send to Litle.
     *
     * @param {Drupal.settings.vantivSettings} settings
     *
     * @returns {object} HTML field elements keyed by Vantiv request keys.
     */
    getCommerceFormFields: function (settings) {

      // Some form fields will always be the same.
      var formFields = {
        'paypageRegistrationId': $('#vantivResponsePaypageRegistrationId').get(0),
        'bin': $('#vantivResponseBin').get(0)
      };
      // Some fields will change based on context.
      var parents = settings.parents.map(function (item) {
        return item.replace(/_/g, '-');
      })
      var parentsId = 'edit-' + parents.join('-');
      formFields.accountNum = $('[data-drupal-selector=' + parentsId + '-number' + ']').get(0);
      formFields.cvv2 = $('[data-drupal-selector=' + parentsId + '-security-code' + ']').get(0);

      return formFields;
    },

    /**
     * Sets hidden form values based on API response.
     *
     * @param {object} response
     */
    setLitleResponseFields: function (response) {
      $('#vantivResponseCode').val(response.response);
      $('#vantivResponseMessage').val(response.message);
      $('#vantivResponseTime').val(response.responseTime);
      $('#vantivResponseLitleTxnId').val(response.litleTxnId);
      $('#vantivResponseType').val(response.type);
      $('#vantivResponseFirstSix').val(response.firstSix);
      $('#vantivResponseLastFour').val(response.lastFour);
    },

    /**
     * Timeout callback.
     */
    timeoutOnLitle: function () {
      alert('We are experiencing technical difficulties (timeout). Please try again later.');
    },

    /**
     * Error callback.
     *
     * @param response
     *
     * @returns {boolean}
     */
    onErrorAfterLitle: function (response) {
      Drupal.vantivEprotect.setLitleResponseFields(response);
      if (response.response == '871') {
        alert('Invalid card number. Check and retry. (Not Mod10)');
      }
      else if (response.response == '872') {
        alert('Invalid card number. Check and retry. (Too short)');
      }
      else if (response.response == '873') {
        alert('Invalid card number. Check and retry. (Too long)');
      }
      else if (response.response == '874') {
        alert('Invalid card number. Check and retry. (Not a number)');
      }
      else if (response.response == '875') {
        alert('We are experiencing technical difficulties. Please try again later.');
      }
      else if (response.response == '876') {
        alert('Invalid card number. Check and retry. (Failure from Server)');
      }
      else if (response.response == '881') {
        alert('Invalid card validation code. Check and retry. (Not a number)');
      }
      else if (response.response == '882') {
        alert('Invalid card validation code. Check and retry. (Too short)');
      }
      else if (response.response == '883') {
        alert('Invalid card validation code. Check and retry. (Too long)');
      }
      else if (response.response == '889') {
        alert('We are experiencing technical difficulties. Please try again later.');
      }

      return false;
    },

    /**
     * Gets a request object from the fields of the current payment form.
     *
     * @param {Drupal~settings~vantivSettings} settings
     *
     * @return {object}
     *   An object with the properties of the request required when calling Vantiv.
     */
    getRequest: function (settings) {
      return {
        paypageId: $('#vantivRequestPaypageId').val(),
        id: $('#vantivRequestMerchantTxnId').val(),
        orderId: $('#vantivRequestOrderId').val(),
        reportGroup: $('#vantivRequestReportGroup').val(),
        url: Drupal.vantivEprotect.getPayPageHost(settings)
      };
    }

  }

})(jQuery, Drupal);
