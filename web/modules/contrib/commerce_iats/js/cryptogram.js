(function (Drupal, $) {

  var script = null;

  Drupal.behaviors.commerceIatsCryptogram = {
    attach: function (context) {
      $('div.commerce-iats-hosted', context).each(function () {
        initCryptogram(this);
      });
    },
    detach: function (context) {
      $('div.commerce-iats-hosted', context).each(function () {
        if (script) {
          script.parentNode.removeChild(script);
          script = null;
        }
      });
    }
  };

  /**
   * Initialize the cryptogram library with attributes from the hosted form.
   */
  function initCryptogram(el) {
    if (script) {
      return;
    }

    var s = document.createElement('script');
    s.type = 'text/javascript';
    s.src = 'https://secure.1stpaygateway.net/restgw/cdn/cryptogram.min.js';
    s.id = 'checkout-js';
    s.dataset.transcenter = el.dataset.transcenter;
    s.dataset.processor = el.dataset.processor;
    s.dataset.type = el.dataset.type;
    s.dataset.form = el.dataset.form;
    // @TODO: attributes for additional items.
    document.body.appendChild(s);
    script = s;
  }

}) (Drupal, jQuery);
