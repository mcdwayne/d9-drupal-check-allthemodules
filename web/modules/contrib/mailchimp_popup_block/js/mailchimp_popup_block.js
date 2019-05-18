/**
 * @file
 * Mailchimp Popup Block listener.
 */
(function (Drupal, window, document) {
  'use strict';

  var mailchimpPopupLoader = document.createElement('script');
  mailchimpPopupLoader.src = '//downloads.mailchimp.com/js/signup-forms/popup/embed.js';
  mailchimpPopupLoader.setAttribute('data-dojo-config', 'usePlainJson: true, isDebug: false');

  Drupal.behaviors.mailchimpPopupBlockListener = {
    attach: function (context) {
      document.body.appendChild(mailchimpPopupLoader);

      var trigger = context.querySelector('.mailchimp-popup-block__trigger');
      if (trigger) {
        trigger.addEventListener('click', function (e) {
          e.preventDefault();

          var baseUrl = this.getAttribute('data-mailchimp-popup-block-baseurl');
          var uuid = this.getAttribute('data-mailchimp-popup-block-uuid');
          var lid = this.getAttribute('data-mailchimp-popup-block-lid');

          var config = {
            baseUrl: baseUrl,
            uuid: uuid,
            lid: lid
          };

          require(['mojo/signup-forms/Loader'], function (L) {
            L.start(config);
          });

          // Set cookies to force opening the popup.
          document.cookie = 'MCPopupClosed=;path=/;expires=Thu, 01 Jan 1970 00:00:00 UTC;';
          document.cookie = 'MCPopupSubscribed=;path=/;expires=Thu, 01 Jan 1970 00:00:00 UTC;';
        });
      }
    }
  };

})(Drupal, this, this.document);
