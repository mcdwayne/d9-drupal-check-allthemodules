(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.cashpressoProductPreview = {
    attach: function (context) {
      $(context).find('.c2-financing-label').once('init-cashpresso-product').each(function () {
        var cpSettings = drupalSettings.commerce_cashpresso;
        var cp = document.createElement('script');
        cp.id = 'c2LabelScript';
        cp.type = 'text/javascript';
        jQuery.each(cpSettings.data, function (key, value) {
          cp.setAttribute('data-c2-' + key, value);
        });
        cp.src = cpSettings.url;
        cp.onload = function () {
          if (window.C2EcomWizard) {
            window.C2EcomWizard.init();
          }
        };
        var s = document.getElementsByTagName('script')[0];
        s.parentNode.insertBefore(cp, s);
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
