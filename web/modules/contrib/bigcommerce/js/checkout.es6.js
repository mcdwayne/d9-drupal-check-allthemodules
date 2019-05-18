import { embedCheckout } from '@bigcommerce/checkout-sdk';

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.bigcommerce = {
    attach(context) {
      if (context === document) {
        let config = {
          containerId: "bigcommerce-checkout-container",
          url: drupalSettings.bigCommerceCheckoutUrl,
          onComplete: () => {
            $.ajax('/checkout/' + drupalSettings.bigCommerceOrderId + '/finalize');
          }
        }

        embedCheckout(config);
      }
    }
  }

}(jQuery, Drupal, drupalSettings));
