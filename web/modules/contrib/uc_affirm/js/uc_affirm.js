/**
 * @file
 * Initializes Affirm API.
 */

(function ($) {
  Drupal.behaviors.uc_affirm_checkout = {
    attach: function (context) {
      var settings = drupalSettings.uc_affirm;
      var url = (settings.ApiMode == 'live')
          ? 'https://cdn1.affirm.com/js/v2/affirm.js'
          : 'https://cdn1-sandbox.affirm.com/js/v2/affirm.js';
      $(document).ready(function () {
        $('#edit-submit').click(function (event) {
          event.preventDefault();
          var _affirm_config = {
            public_api_key: settings.hasOwnProperty('PublicKey') ? settings.PublicKey : '',
            script: url
          };

          (function (l, g, m, e, a, f, b) {
            var d, c = l[m] || {}, h = document.createElement(f), n = document.getElementsByTagName(f)[0], k = function (a, b, c) {
              return function () {
                a[b]._.push([c, arguments])
              }
            };
            c[e] = k(c, e, "set");
            d = c[e];
            c[a] = {};
            c[a]._ = [];
            d._ = [];
            c[a][b] = k(c, a, b);
            a = 0;
            for (b = "set add save post open empty reset on off trigger ready setProduct".split(" "); a < b.length; a++)
              d[b[a]] = k(c, e, b[a]);
            a = 0;
            for (b = ["get", "token", "url", "items"]; a < b.length; a++)
              d[b[a]] = function () {
              };
            h.async = !0;
            h.src = g[f];
            n.parentNode.insertBefore(h, n);
            delete g[f];
            d(g);
            l[m] = c
          })(window, _affirm_config, "affirm", "checkout", "ui", "script", "ready");
          var billingFirst, billingLast, billingAddress1, billingAddress2, billingCity, billingState, billingEmail, billingZip, billingPhone, totalRaw, total, productName, unitPriceRaw, unitPrice, productQuantity, taxRaw, tax, shippingRaw, shipping, discountRaw, discount, calculatedTotal, calculatedSubtotal;
          var discount = 0;
          affirm.checkout({
            "config": {
              "financial_product_key": settings.FinancialProductKey
            },
            "merchant": {
              "user_cancel_url": settings.CancelUrl,
              "user_confirmation_url": settings.ConfirmUrl,
              "user_confirmation_url_action": "POST",
              "charge_declined_url": settings.CancelUrl,
            },
            "items": settings.items,
            "discounts": {
              "discount_name": {
                "discount_amount": discount
              }
            },
            "order_id": settings.OrderId,
            "metadata": {
              "shipping_type": "UPS Ground"
            },
            "shipping": {
              "name": {
                "first": settings.ShippingFirstName,
                "last": settings.ShippingLastName
              },
              "address": {
                "line1": settings.ShippingAddressLn1,
                "line2": settings.ShippingAddressLn2,
                "city": settings.ShippingAddressCity,
                "state": settings.ShippingAddressState,
                "zipcode": settings.ShippingAddressPostCode
              },
              "phone_number": settings.ShippingTelephone,
              "email": settings.Email,
            },
            "billing": {
              "name": {
                "first": settings.BillingFirstName,
                "last": settings.BillingLastName
              },
              "address": {
                "line1": settings.BillingAddressLn1,
                "line2": settings.BillingAddressLn2,
                "city": settings.BillingAddressCity,
                "state": settings.BillingAddressState,
                "zipcode": settings.BillingAddressPostCode
              },
              "phone_number": settings.BillingTelephone,
              "email": settings.Email,
            },
            "shipping_amount": settings.ShippingTotal,
            "tax_amount": settings.TaxAmount,
            "total": settings.ProductsTotal
          });

          affirm.checkout.post();
        });
      });
    }
  }
})(jQuery);
