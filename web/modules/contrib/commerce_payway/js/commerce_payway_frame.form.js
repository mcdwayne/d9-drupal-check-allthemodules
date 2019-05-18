/**
 * @file
 * Javascript to generate PayWay Frame token in PCI-compliant way.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  var frameInstance = null;

  /**
   * Attaches the commercePayWayFrameForm behavior.
   *
   * @type {Drupal~behavior}
   *
   * @see Drupal.commercePayWayFrameForm
   */
  Drupal.behaviors.commercePayWayFrameForm = {

    attach: function (context) {
      $(context).find('#payway-credit-card').once('commercePayWayFrameForm').each(
        function () {
          if (frameInstance !== null) {
            frameInstance.destroy();
          }

          payway.createCreditCardFrame(
            {
              publishableApiKey: drupalSettings.commercePayWayFrameForm.publishableKey
            }, function (err, frame) {
              frameInstance = frame;
            }
          );
        }
      );

    }

  };

})(jQuery, Drupal, drupalSettings);
