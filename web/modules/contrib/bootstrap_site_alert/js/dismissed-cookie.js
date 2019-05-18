
(function ($, Drupal) {
  Drupal.behaviors.bootstrapSiteAlert = {
    attach: function (context, drupalSettings) {

      // Since the key is updated every time the configuration form is saved,
      // we can ensure users don't miss newly added or changed alerts.
      var key = drupalSettings.bootstrap_site_alert.dismissedCookie.key;

      // Only show the alert if dismiss button has not been clicked. The
      // element is hidden by default in order to prevent it from momentarily
      // flickering onscreen. We are not working with Bootstrap's 'hide' class
      // since we don't want a dependency on Bootstrap.
      if ($.cookie('Drupal.visitor.bootstrap_site_alert_dismissed') !== key) {
        $('.bs-site-alert').css('display', 'block');
      }

      // Set the cookie value when dismiss button is clicked.
      $('.bs-site-alert .close').click(function(e) {

        // Do not perform default action.
        e.preventDefault();

        // Set cookie to the current key.
        $.cookie('Drupal.visitor.bootstrap_site_alert_dismissed', key, { path: drupalSettings.path.baseUrl });
      });
    }
  }
})(jQuery, Drupal);
