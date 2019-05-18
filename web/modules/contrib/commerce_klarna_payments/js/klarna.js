/**
 * @file
 * Klarna payments widget.
 */

(function (window, Drupal, drupalSettings, $, Klarna) {

  'use strict';

  /**
   * Provides the Klarna payments widget.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the klarna payments.
   */
  Drupal.behaviors.klarnaPayments = {
    settings: {},
    selectedPaymentMethod: null,

    attach: function (context) {
      this.initialize(drupalSettings.klarnaPayments);

      riot.mount('klarna', this);
    },

    initialize: function(settings) {
      this.settings = settings;

      Klarna.Payments.init({
        client_token: settings.client_token
      });
    },

    load: function(method, data, callback) {
      try {
        Klarna.Payments.load({
            container: '#klarna-payment-container-' + method,
            payment_method_category: method
          },
          data,
          function (response) {
            if (callback) {
              return callback(response);
            }
          });
      }
      catch (e) {
        console.log(e);
      }
    },

    reauthorize: function(method, data, callback) {
      Klarna.Payments.reauthorize({
          payment_method_category: method,
        },
        data,
        function (response) {
          if (callback) {
            return callback(response);
          }
        });
    },

    authorize: function(method, data, callback) {
      Klarna.Payments.authorize({
        payment_method_category: method,
      },
        data,
        function (response) {
          if (callback) {
            return callback(response);
          }
        });
    }
  };

})(window, Drupal, drupalSettings, jQuery, Klarna);
