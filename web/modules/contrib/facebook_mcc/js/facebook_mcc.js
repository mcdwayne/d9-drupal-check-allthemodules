(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.facebook_mcc = {
    attach: function (context, settings) {
      // Load configurations
      var app_id = drupalSettings.facebook_mcc_app_id;
      var localization = drupalSettings.facebook_mcc_local;

      // The following snippet loads and initialises Facebook Messenger Customer Chat plugin.
      window.fbAsyncInit = function () {
        FB.init({
          appId: app_id,
          autoLogAppEvents: true,
          xfbml: true,
          version: 'v2.12'
        });
      };

      (function (d, s, id) {
        var js;
        var fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) {return;}
        js = d.createElement(s); js.id = id;
        js.src = 'https://connect.facebook.net/'+localization+'/sdk/xfbml.customerchat.js';
        fjs.parentNode.insertBefore(js, fjs);
      }(document, 'script', 'facebook-jssdk'));
    }
  };
})(jQuery, Drupal);
