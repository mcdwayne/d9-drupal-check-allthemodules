(function ($, Drupal, drupalSettings) {
  'use strict';

  if (!drupalSettings.commerce_cashpresso) {
    return;
  }
  var cpSettings = drupalSettings.commerce_cashpresso;
  if (!cpSettings.directCheckoutUrl) {
    return;
  }

  window.c2Checkout = function() {
    var purchasableEntityId = $('.cashpresso-product-label').data('purchasable-entity');
    if (!purchasableEntityId) {
      console.log('Cannot proceed to checkout: no/invalid financing label.');
      return;
    }
    window.location = cpSettings.directCheckoutUrl;
  };

})(jQuery, Drupal, drupalSettings);
