/**
 * @file
 */

Drupal.behaviors.stripe_checkout = {
  attach: function (context, settings) {

    function getParameterByName(name, url) {
      if (!url) {
        url = window.location.href;
      }
      name = name.replace(/[\[\]]/g, "\\$&");
      var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
      if (!results) {
        return null;
      }
      if (!results[2]) {
        return '';
      }
      return decodeURIComponent(results[2].replace(/\+/g, " "));
    }

    (function ($) {
      // If stripe_checkout_click query parameter is set, then click the stripe button automatically.
      var stripe_checkout_click = getParameterByName('stripe_checkout_click');
      if (stripe_checkout_click) {
        $('.stripe-button-el', context).once('clicked').click();
      }

    })(jQuery);
  }


};
