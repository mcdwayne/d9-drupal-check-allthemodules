(function ($, Drupal, drupalSettings) {
  'use strict';

  if (!drupalSettings.commerce_cashpresso) {
    return;
  }
  var cpSettings = drupalSettings.commerce_cashpresso;
  var cp = document.createElement('script');
  cp.id = 'c2StaticLabelScript';
  cp.type = 'text/javascript';
  cp.setAttribute('defer', 'true');
  jQuery.each(cpSettings.data, function (key, value) {
    cp.setAttribute('data-c2-' + key, value);
  });
  cp.src = cpSettings.url;
  var s = document.getElementsByTagName('script')[0];
  s.parentNode.insertBefore(cp, s);

  Drupal.behaviors.cashpressoProductPreviewStatic = {
    attach: function (context) {
      $(context).find('.c2-static-label').once('init-cashpresso-product-static').each(function () {
        $(this).on('click', function(e) {
          e.preventDefault();
          var amount = $(this).data('amount');
          C2EcomWizard.startOverlayWizard(amount);
        });
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
