(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.initCashpressoCheckout = {
    attach: function (context) {
      var $cpCheckout = $(context).find('#cashpresso-checkout');

      if ($cpCheckout.length > 0) {
        $cpCheckout.once('init-cashpresso-checkout').each(function () {
          var cpSettings = drupalSettings.commerce_cashpresso;
          var cp = document.createElement('script');
          cp.id = 'c2CheckoutScript';
          cp.type = 'text/javascript';
          jQuery.each(cpSettings.data, function (key, value) {
            cp.setAttribute('data-c2-' + key, value);
          });
          cp.src = cpSettings.url;
          cp.onload = function () {
            if (window.C2EcomCheckout) {
              window.C2EcomCheckout.init();
            }
          };
          var s = document.getElementsByTagName('script')[0];
          s.parentNode.insertBefore(cp, s);
        });
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
