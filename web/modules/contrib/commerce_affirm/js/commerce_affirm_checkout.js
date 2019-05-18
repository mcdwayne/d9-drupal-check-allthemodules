/**
 * @file
 * Send a checkout request to Affirm API.
 */

(function ($, Drupal) {
  /**
   * Organises and sends content to the Affirm server.
   */
  Drupal.behaviors.commerce_affirm_checkout = {
    attach: function (context, settings) {
      var config = settings.commerce_affirm;
      affirm.checkout({
        "merchant": {
          "user_confirmation_url": config.ConfirmUrl,
          "user_confirmation_url_action": "POST",
          "user_cancel_url": config.CancelUrl
        },
        "config": {
          "financial_product_key": config.FinancialProductKey
        },
        "billing": {
          "name": {
            "full": config.BillingFullName,
            "first": config.BillingFirstName,
            "last": config.BillingLastName
          },
          "address": {
            "line1": config.BillingAddressLn1,
            "line2": config.BillingAddressLn2,
            "city": config.BillingAddressCity,
            "state": config.BillingAddressState,
            "zipcode": config.BillingAddressPostCode,
            "country": config.BillingAddressCountry
           },
          "email": config.Email,
          "phone_number": config.BillingTelephone
        },
        "items": config.items,
        "shipping": {
          "name": {
            "full": config.ShippingFullName,
            "first": config.ShippingFirstName,
            "last": config.ShippingLastName
          },
          "address": {
            "line1": config.ShippingAddressLn1,
            "line2": config.ShippingAddressLn2,
            "city": config.ShippingAddressCity,
            "state": config.ShippingAddressState,
            "zipcode": config.ShippingAddressPostCode,
            "country": config.ShippingAddressCountry
          },
          "email": config.Email,
          "phone_number": config.ShippingTelephone
        },
        "discounts": config.discounts,
        "metadata": config.metadata,
        "shipping_amount": config.ShippingTotal,
        "tax_amount": config.TaxAmount,
        "total": config.ProductsTotal
      });

      // Submit and redirect to checkout flow.
      affirm.checkout.post();
    }
  }
})(jQuery, Drupal);
