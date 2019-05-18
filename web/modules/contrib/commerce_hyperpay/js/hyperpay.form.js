var wpwlOptions;

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.initHyperpayPayment = {
    attach: function (context) {
      var $form = $(context).find('.paymentWidgets');

      if ($form.length > 0) {
        $form.once('hyperpay-payment-form').each(function () {
          var hyperpay_settings = drupalSettings.commerce_hyperpay;
          wpwlOptions = {
            locale: hyperpay_settings.langcode
          };
          var hyperpay = document.createElement('script');
					hyperpay.type = 'text/javascript';
					hyperpay.src = hyperpay_settings.hyperpay_url;
          var s = document.getElementsByTagName('script')[0];
          s.parentNode.insertBefore(hyperpay, s);
        });
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
