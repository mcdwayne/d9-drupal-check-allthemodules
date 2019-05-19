"use strict";

(function ($) {

  Drupal.behaviors.video_embed_damdy = {
    attach: function (context, setting) {
      if (typeof setting !== 'undefined' && typeof setting.damdy_config_js !== 'undefined') {
        var videos = context.querySelectorAll('.damdy_player');
        if (videos.length > 0) {
          var damdy_js_config = setting.damdy_config_js;
          (function (window) {
            var document = window.document;
            var js = document.createElement('script');
            js.type = 'text/javascript';
            js.async = true;
            js.src = damdy_js_config;
            var s = document.getElementsByTagName('script')[0];
            s.parentNode.insertBefore(js, s);
          })(window);
        }
      }
    }
  };

  // Fix Video Damdy in BO listig atoms.
  jQuery(document).ajaxComplete(function (e, xhr, settings) {
    Drupal.behaviors.video_embed_damdy.attach();
  });
})(jQuery);
