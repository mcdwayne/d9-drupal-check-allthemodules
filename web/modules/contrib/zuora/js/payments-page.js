/**
 * @file
 * Defines behaviors for the Zuora Hosted Payment Pages.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  /**
   * Attaches the zuoraPaymentsPage behavior.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the zuoraPaymentsPage behavior.
   */
  Drupal.behaviors.zuoraPaymentsPage = {
    attach: function (context) {
      var params = drupalSettings.zuoraPaymentPage.params || {};
      var fields = drupalSettings.zuoraPaymentPage.fields || {};

      var waitForSdk = setInterval(function () {
        if (typeof Z !== 'undefined') {
          clearInterval(waitForSdk);
          Drupal.zuora.renderFrame(params, fields);
        }
      }, 100);
    }
  };

  Drupal.zuora = Drupal.zuora || {};

  Drupal.zuora.renderFrame = function (params, fields) {
    $('#zuora_payment').once('zuora').each(function () {
      Z.render(
        params,
        fields,
        Drupal.zuora.frameCallback
      );
    });
    if (params.submitEnabled === false) {
      var $zuoraPaymentSubmit = $('.zuora-payment-submit-button');
      $zuoraPaymentSubmit.once('zuora').click(function (e) {
        e.preventDefault();
        Z.validate(function (response) {
          if (response.success === true) {
            $zuoraPaymentSubmit.disabled = true;
            $zuoraPaymentSubmit.addClass('hidden');
            $(document).find('.zuora-payment-loading').removeClass('hidden');
            $(document).find('#zuora_payment').addClass('hidden');
            Drupal.zuora.submitEnabledSubmitted();
            Z.submit();
          }
        });
      });
    }
  };

  Drupal.zuora.frameCallback = function (response) {
    if (response.action !== 'validate') {
      var redirect = '';
      if (response.success === true || response.success === 'true') {
        redirect = drupalSettings.zuoraPaymentPage.nextPage + '?refid=' + response.refId;
      }
      else {
        redirect = drupalSettings.zuoraPaymentPage.prevPage + '?zuoraEc=' + response.errorCode + '&zuoraEm=' + response.errorMessage;
      }
      window.location.replace(redirect);
    }
  };

  Drupal.zuora.submitEnabledSubmitted = function () { };
})(jQuery, Drupal, drupalSettings);
