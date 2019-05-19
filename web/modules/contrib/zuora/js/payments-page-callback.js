/**
 * @file
 * Defines behaviors for the Zuora Hosted Payment Page Callback.
 */

(function (Drupal, drupalSettings) {
  'use strict';
  top.Drupal.zuora.frameCallback(drupalSettings.zuoraPaymentPageCallback);
})(Drupal, drupalSettings);
