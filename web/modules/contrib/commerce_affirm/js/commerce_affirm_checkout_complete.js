/**
 * @file
 * Enhanced Affirm Analytics.
 */

(function ($, Drupal) {
  Drupal.behaviors.commerce_affirm_checkout_complete = {
    attach: function (context, settings) {
      affirm.ui.ready(function() {
        affirm.analytics.trackOrderConfirmed(settings.commerceAffirmAnalytics.checkoutComplete.order, settings.commerceAffirmAnalytics.checkoutComplete.products);
      });
    }
  }
})(jQuery, Drupal);
