(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.piwikNoscript = {
    attach: function (context, settings) {
      $(document.body, context).once('piwik-noscript').each(function () {
        $.post({
          xhrFields: {withCredentials: true},
          url: settings.piwikNoscript.url.replace('urlref=', 'urlref=' + encodeURIComponent(document.referrer))
        });
      });
    }
  };

})(jQuery, Drupal);
