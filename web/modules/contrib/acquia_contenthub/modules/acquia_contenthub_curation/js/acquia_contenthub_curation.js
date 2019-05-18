/**
 * @file
 * Content Hub Subscriber.
 */

(function ($) {
  window.onload = function () {
    var ember_app = drupalSettings.acquia_contenthub_curation.ember_app;
    // @Todo Need to discuss how creating an iframe on the fly helps.
    // Var iframe = document.createElement('iframe');
    // iframe.setAttribute('id', 'receiver');
    // $('#content').append(iframe);
    // iframe.setAttribute('src', ember_app);
    // iframe.setAttribute('width', '100%');
    // iframe.setAttribute('style', 'border:0');
    // iframe.setAttribute('height', '1000px');
    if (drupalSettings.acquia_contenthub_curation.ember_app !== null) {
      var receiver = document.getElementById('acquia-contenthub-ember').contentWindow
      if (receiver) {
        receiver.postMessage(drupalSettings.acquia_contenthub_curation, ember_app);
      };
    }
  }
})(jQuery);
