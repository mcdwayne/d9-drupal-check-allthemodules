(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.snapengage = {
    attach: function (context, settings) {
        $(document).ready(function () {
  (function() {
    var widget_id = drupalSettings.snapengage.snapengage.widget_id;
    var default_language = drupalSettings.snapengage.snapengage.default_language;
    var se = document.createElement('script'); se.type = 'text/javascript'; se.async = true;
    se.src = '//storage.googleapis.com/code.snapengage.com/js/' + widget_id + '.js';
    var done = false;
    se.onload = se.onreadystatechange = function() {
      if (!done&&(!this.readyState||this.readyState==='loaded'||this.readyState==='complete')) {
        done = true;
     SnapEngage.setLocale(default_language);
      }
    };
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(se, s);
  })();
           });
    }
  };
})(jQuery, Drupal);



