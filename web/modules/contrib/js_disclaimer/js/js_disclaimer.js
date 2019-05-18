(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.JsDisclaimerBehavior = {
    attach: function (context, settings) {
      var hostName = window.location.hostname;
      var disclaimerMessage = ((drupalSettings.jsDisclaimer.disclaimerMessage.length > 0) ? drupalSettings.jsDisclaimer.disclaimerMessage : Drupal.t('You are navigating away from the website. Do you want to proceed?'));

      $("a[href*='" + hostName + "']").addClass("not-external");
      $("a[href^='http']:not('.not-external')")
        .unbind("click")
        .bind("click", function () {
          return confirm(disclaimerMessage);
        });
    }
  };
})(jQuery, Drupal, drupalSettings);
