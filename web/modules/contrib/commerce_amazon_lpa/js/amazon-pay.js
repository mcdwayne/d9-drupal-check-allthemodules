(function ($, Drupal, settings) {
  'use strict';
  Drupal.amazonPay = {
    widgetsJsUrl: '',
    merchantId: '',
    authRequest: '',
    clientId: '',
    checkoutUrl: '',
    langcode: '',
    orderReferenceId: '',
    isShippable: true,
    loginOptions: {
      scope: 'profile postal_code payments:widget payments:shipping_address',
      popup: true
    },
    addressBookOptions: {
      displayMode: 'edit'
    },
    walletOptions: {
      displayMode: 'edit'
    },
    errorHandler: function (f) {
      var f1 = f.getErrorCode();
      var f2 = f.getErrorMessage();

      if (f1 === 'BuyerSessionExpired') {
        // @todo actually handle re-auth on same page
        // When runing `akazon.Login.authorize` popup blocker caught it.
        alert(Drupal.t('Your Amazon Pay session expired, please restart the checkout process'));
        window.location = Drupal.url('cart');
      }

      console.log(f1 + f2);
    },
    initialize: function () {
      $(document).find('[data-amazon-button]').each(this.Button);
      $(document).find('[data-amazon-widget="addressbook"]').each(this.Addressbook);
      $(document).find('[data-amazon-widget="wallet"]').each(this.Wallet);
    },
    Button: function () {
      var el = this;
      OffAmazonPayments.Button(el.id, Drupal.amazonPay.merchantId, {
        type: el.dataset['amazonButton'],
        color: el.dataset['style'],
        size: el.dataset['size'],
        language: Drupal.amazonPay.langcode,
        useAmazonAddressBook: true,
        authorization: function () {
          var loginOptions = Drupal.amazonPay.loginOptions;
          Drupal.amazonPay.authRequest = amazon.Login.authorize(loginOptions, el.dataset['url']);
        },
        onError: function (error) {
          Drupal.amazonPay.errorHandler(error);
        }
      });
    },
    Addressbook: function () {
      var el = this;
      new OffAmazonPayments.Widgets.AddressBook({
        sellerId: Drupal.amazonPay.merchantId,
        amazonOrderReferenceId: Drupal.amazonPay.orderReferenceId || null,
        displayMode: el.dataset['displayMode'],
        design: {
          designMode: 'responsive'
        },
        getContractId: function () {
          if (Drupal.amazonPay.orderReferenceId) {
            return Drupal.amazonPay.orderReferenceId;
          }

          return null;
        },
        onOrderReferenceCreate: function (orderReference) {
          if (!Drupal.amazonPay.orderReferenceId) {
            Drupal.amazonPay.orderReferenceId = orderReference.getAmazonOrderReferenceId();
            var $referenceIdField = $('input[name="amazon_order_reference_id"]', document);
            if ($referenceIdField.length > 0) {
              $referenceIdField.val(orderReference.getAmazonOrderReferenceId());
              this.amazonOrderReferenceId = orderReference.getAmazonOrderReferenceId();
            }
          }
        },
        onAddressSelect: function (orderReference) {
          var $calculateShipping = $('[data-drupal-selector="edit-shipping-recalculate-shipping"]');
          if ($calculateShipping.length > 0) {
            $calculateShipping.trigger('mousedown');
          }
        },
        onError: function (error) {
          Drupal.amazonPay.errorHandler(error);
        }
      }).bind(el.id);
    },
    Wallet: function () {
      var el = this;
      var onOrderReferenceCreate = null;
      if (!Drupal.amazonPay.isShippable) {
        onOrderReferenceCreate = function (orderReference) {
          // Use the following code to get the generated Order Reference ID.
          var $referenceIdField = $('input[name="amazon_order_reference_id"]', document);
          if ($referenceIdField.length > 0 && $referenceIdField.val() === '') {
            $referenceIdField.val(orderReference.getAmazonOrderReferenceId());
          }
        };
      }

      new OffAmazonPayments.Widgets.Wallet({
        sellerId: Drupal.amazonPay.merchantId,
        amazonOrderReferenceId: Drupal.amazonPay.orderReferenceId || null,
        displayMode: el.dataset['displayMode'],
        onPaymentSelect: function () {

        },
        onOrderReferenceCreate: onOrderReferenceCreate,
        design: {
          designMode: 'responsive'
        },
        onError: function (error) {
          Drupal.amazonPay.errorHandler(error);
        }
      }).bind(el.id);
    }
  };

  $(function () {
    var ws = document.createElement('script');
    $.extend(true, Drupal.amazonPay, settings.amazonPay);
    ws.type = 'text/javascript';
    ws.src = Drupal.amazonPay.widgetsJsUrl;
    ws.id = 'AmazonLPAWidgets';
    ws.async = true;
    document.getElementsByTagName('head')[0].appendChild(ws);

    window.onAmazonLoginReady = function () {
      amazon.Login.setClientId(Drupal.amazonPay.clientId);
      amazon.Login.setUseCookie(true);
    };
    window.onAmazonPaymentsReady = function () {
      Drupal.amazonPay.initialize();
    };
  });

  Drupal.behaviors.commerceAmazonLPA = {
    attach: function (context, settings) {
      if (typeof amazon !== 'undefined' && context === document) {
        Drupal.amazonPay.initialize();
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
