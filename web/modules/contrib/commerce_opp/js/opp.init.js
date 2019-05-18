var wpwlOptions;

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.initOppPayment = {
    attach: function (context) {
      var $form = $(context).find('.paymentWidgets');

      if ($form.length > 0) {
        $form.once('opp-payment-form').each(function () {
          var opp_settings = drupalSettings.commerce_opp;
          wpwlOptions = {
            locale: opp_settings.langcode
          };
          if (opp_settings.sofort_countries) {
            wpwlOptions.sofortCountries = opp_settings.sofort_countries;
          }
          var opp = document.createElement('script');
          opp.type = 'text/javascript';
          opp.src = opp_settings.opp_script_url;
          var s = document.getElementsByTagName('script')[0];
          s.parentNode.insertBefore(opp, s);
        });
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
