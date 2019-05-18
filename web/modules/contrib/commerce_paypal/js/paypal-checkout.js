(function ($, Drupal) {
  'use strict';

  Drupal.paypalCheckout = {
    makeCall: function(url, settings) {
      settings = settings || {};
      var ajaxSettings = {
        dataType: 'json',
        url: url
      };
      $.extend(ajaxSettings, settings);
      return $.ajax(ajaxSettings);
    },
    renderButtons: function(settings) {
      $(settings['elementSelector']).once().each(function() {
        paypal.Buttons({
          createOrder: function() {
            return Drupal.paypalCheckout.makeCall(settings.onCreateUrl).then(function(data) {
              return data.id;
            });
          },
          onApprove: function (data) {
            Drupal.paypalCheckout.addLoader();
            return Drupal.paypalCheckout.makeCall(settings.onApproveUrl, {
              type: 'POST',
              contentType: "application/json; charset=utf-8",
              data: JSON.stringify({
                id: data.orderID,
                flow: settings.flow
              })
            }).then(function(data) {
              if (data.hasOwnProperty('redirectUrl')) {
                window.location.assign(data.redirectUrl);
              }
              else {
                // Force a reload to see the eventual error messages.
                location.reload(true);
              }
            });
          },
          style: settings['style']
        }).render('#' + $(this).attr('id'));
      });
    },
    initialize: function (context, settings) {
      if (context === document) {
        var script = document.createElement('script');
        script.src = settings.src;
        script.type = 'text/javascript';
        script.setAttribute('data-partner-attribution-id', 'CommerceGuys_Cart_SPB');
        document.getElementsByTagName('head')[0].appendChild(script);
      }
      var waitForSdk = function(settings) {
        if (typeof paypal !== 'undefined') {
          Drupal.paypalCheckout.renderButtons(settings);
        }
        else {
          setTimeout(function() {
            waitForSdk(settings)
          }, 100);
        }
      };
      waitForSdk(settings);
    },
    addLoader: function() {
      var $background = $('<div id="paypal-background-overlay"></div>');
      var $loader = $('<div class="paypal-background-overlay-loader"></div>');
      $background.append($loader);
      $('body').append($background);
    }
  };

  Drupal.behaviors.commercePaypalCheckout = {
    attach: function (context, settings) {
      Drupal.paypalCheckout.initialize(context, settings.paypalCheckout);
    }
  };

})(jQuery, Drupal);
