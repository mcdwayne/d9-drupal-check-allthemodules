(function($, Drupal, drupalSettings) {
  Drupal.behaviors.main = {
    attach: function(context, settings) {
      if(drupalSettings.cincopa) {
        for (var key in drupalSettings.cincopa) {
          arg1 = drupalSettings.cincopa[key].arg0;
          arg2 = drupalSettings.cincopa[key].arg2;
          cp_load_widget(arg1, arg2);
        }
      }
    }
  }

  jQuery(document).ready(function(){
    if(getCookie('cincopa_help_close')) {
      jQuery(".cincopa_help_wrapper").hide();
    }

    jQuery("#icon_close").on("click", function(){
      jQuery(".cincopa_help_wrapper").slideUp();
      setCookie('cincopa_help_close','1');
    });
  });

  function setCookie(key, value) {
    var expires = new Date();
    expires.setTime(expires.getTime() + (60 * 1 * 24 * 60 * 60 * 1000));
    document.cookie = key + '=' + value + ';expires=' + expires.toUTCString() + '; path=/';
  }

  function getCookie(key) {
    var keyValue = document.cookie.match('(^|;) ?' + key + '=([^;]*)(;|$)');
    return keyValue ? keyValue[2] : null;
  }
})(jQuery, Drupal, drupalSettings);